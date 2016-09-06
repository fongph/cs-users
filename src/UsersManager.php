<?php

namespace CS\Users;

use PDO,
    IP,
    CS\Settings\GlobalSettings,
    CS\Mail\MailSender,
    EventManager\EventManager,
    CS\Models\User\UserRecord,
    CS\Models\User\AuthLog\UserAuthLogRecord,
    CS\Models\User\Options\UserOptionRecord;

/**
 * Description of Manager
 *
 * @author root
 */
class UsersManager {

    /**
     * Database connection
     * 
     * @var PDO
     */
    protected $db;

    /**
     *
     * @var UsersNotes
     */
    protected $usersNotes;

    /**
     *
     * @var MailSender 
     */
    protected $sender;
    protected $loginAttempts = 5;
    protected $loginAttemptsPeriod = 300; // 5 min

    /**
     *
     * @var boolean
     */
    private static $listenersRegistered = false;

    public function __construct(\PDO $db)
    {
        $this->db = $db;

        self::registerListeners($db);
    }

    /**
     * 
     * @return PDO
     */
    public function getDb()
    {
        return $this->db;
    }

    public function setSender(MailSender $sender)
    {
        $this->sender = $sender;

        return $this;
    }

    public function getSender()
    {
        if (!($this->sender instanceof MailSender)) {
            throw new InvalidSenderObjectException("Invalid mail sender object!");
        }

        return $this->sender;
    }

    public function getPasswordHash($password)
    {

        return PassHash::hash($password);
    }

    public function verifyPassword($hash, $password)
    {
        return PassHash::check_password($hash, $password);
    }

    public function setUsersNotesProcessor(UsersNotes $usersNotes)
    {
        $this->usersNotes = $usersNotes;

        return $this;
    }

    /**
     * 
     * @todo remove creating instance inside by default
     * @return UsersNotes
     */
    private function getUsersNotesProcessor()
    {
        if (!($this->usersNotes instanceof UsersNotes)) {
            $this->usersNotes = new UsersNotes($this->db);
        }

        return $this->usersNotes;
    }

    public function setUserOption($userId, $option, $value, $scope = UserOptionRecord::SCOPE_GLOBAL)
    {

        $escapedUserId = $this->getDb()->quote($userId);
        $escapedOption = $this->getDb()->quote($option);
        $escapedValue = $this->getDb()->quote($value);
        $escapedScope = $this->getDb()->quote($scope);

        $this->getDb()->exec("INSERT INTO `users_options` SET 
                    `user_id` = {$escapedUserId},
                    `option` = {$escapedOption},
                    `value` = {$escapedValue},
                    `scope` = {$escapedScope}
                ON DUPLICATE KEY UPDATE
                    `value` = {$escapedValue},
                    `scope` = {$escapedScope}");

        return $this;
    }

    public function hasUserOption($userId, $option, $scope = null)
    {
        $escapedUserId = $this->getDb()->quote($userId);
        $escapedOption = $this->getDb()->quote($option);

        return $this->getDb()->query("SELECT `value` FROM `users_options` WHERE `user_id` = {$escapedUserId} AND `option` = {$escapedOption} LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    }

    public function getUserOption($userId, $option, $scope = null)
    {
        $escapedUserId = $this->getDb()->quote($userId);
        $escapedOption = $this->getDb()->quote($option);

        if ($scope !== null) {
            $escapedScope = $this->getDb()->quote($scope);
            return $this->getDb()->query("SELECT `value` FROM `users_options` WHERE `user_id` = {$escapedUserId} AND `option` = {$escapedOption} AND `scope` = {$escapedScope} LIMIT 1")->fetchColumn();
        }

        return $this->getDb()->query("SELECT `value` FROM `users_options` WHERE `user_id` = {$escapedUserId} AND `option` = {$escapedOption} LIMIT 1")->fetchColumn();
    }

    public function removeUserOption($userId, $option)
    {
        $escapedUserId = $this->getDb()->quote($userId);
        $escapedOption = $this->getDb()->quote($option);

        $this->getDb()->exec("DELETE FROM `users_options` WHERE `user_id` = {$escapedUserId} AND `option` = {$escapedOption}");

        return $this;
    }

    public function getUserOptions($userId, $scopes = UserOptionRecord::SCOPE_GLOBAL)
    {
        $scopesConditions = array();

        if (is_array($scopes)) {
            foreach ($scopes as $value) {
                if (in_array($value, UserOptionRecord::getAllowedScopes())) {
                    array_push($scopesConditions, '`scope` = ' . $this->db->quote($value));
                }
            }
        } else {
            if (in_array($scopes, UserOptionRecord::getAllowedScopes())) {
                array_push($scopesConditions, '`scope` = ' . $this->db->quote($scopes));
            }
        }

        $escapedUserId = $this->getDb()->quote($userId);

        if (count($scopesConditions)) {
            return $this->getDb()->query("SELECT `option`, `value` FROM `users_options` WHERE `user_id` = {$escapedUserId} AND (" . implode(' OR ', $scopesConditions) . ")")->fetchAll(\PDO::FETCH_KEY_PAIR);
        }

        return $this->getDb()->query("SELECT `option`, `value` FROM `users_options` WHERE `user_id` = {$escapedUserId}")->fetchAll(\PDO::FETCH_KEY_PAIR);
    }

    public function login($siteId, $email, $password, $timezone = '', $environment)
    {
        $data = $this->getUserData($siteId, $email);

        if ($data == false) {
            throw new UserNotFoundException("User not found!");
        }

        if ($data['locked']) {
            throw new UserLockedException("User account is locked out!");
        }

        if (!$this->verifyPassword($data['password'], $password)) {
            $this->incFailAttempts($siteId, $data, $environment);
            throw new InvalidPasswordException("Invalid password!");
        }

        $this->logAuth($data['id'], $timezone, $environment);
        unset($data['locked'], $data['password']);

        $data['options'] = $this->getUserOptions($data['id'], array(UserOptionRecord::SCOPE_GLOBAL, UserOptionRecord::SCOPE_CONTROL_PANEL));

        return $data;
    }

    //@TODO: add transactions support
    public function lostPassword($siteId, $email)
    {
        if (($userId = $this->getUserId($siteId, $email)) === false) {
            throw new UsersEmailNotFoundException();
        }

        $secret = $this->getRandomString();

        $escapedUserId = $this->getDb()->quote($userId);
        $secretValue = $this->getDb()->quote($secret);

        $this->getDb()->exec("UPDATE `users` SET 
                                    `restore_hash` = {$secretValue}, 
                                    `updated_at` = NOW() 
                                WHERE 
                                    `id` = {$escapedUserId}
                                LIMIT 1");

        $restorePasswordUrl = GlobalSettings::getRestorePasswordPageUrl($siteId, $email, $secret);

        $this->getUsersNotesProcessor()->accountRestored($userId);

        $this->getSender()
                ->setUserId($userId)
                ->sendLostPassword($email, $restorePasswordUrl);

        return true;
    }

    private function getUserPasswordHash($id)
    {
        $escapedId = $this->getDb()->quote($id);
        return $this->getDb()->query("SELECT `password` FROM `users` WHERE `id` = {$escapedId} LIMIT 1")->fetchColumn();
    }

    public function updatePassword($id, $oldPassword, $newPassword, $newPasswordConfirm)
    {
        if ($newPassword !== $newPasswordConfirm) {
            throw new PasswordsNotEqualException("Users passwords are not equal!");
        }

        if (strlen($newPassword) < 6) {
            throw new PasswordTooShortException("User password is too short!");
        }

        $hash = $this->getUserPasswordHash($id);

        if (!$this->verifyPassword($hash, $oldPassword)) {
            throw new InvalidPasswordException("Invalid password!");
        }

        $userId = $this->getDb()->quote($id);
        $passwordValue = $this->getDb()->quote($this->getPasswordHash($newPassword));
        $this->getDb()->exec("UPDATE `users` SET `password` = {$passwordValue}, `updated_at` = NOW() WHERE `id` = {$userId}");

        return true;
    }

    public function setUserPassword($id, $password)
    {
        if (strlen($password) < 6) {
            throw new PasswordTooShortException("User password is too short!");
        }

        $userId = $this->getDb()->quote($id);
        $passwordValue = $this->getDb()->quote($this->getPasswordHash($password));
        $this->getDb()->exec("UPDATE `users` SET `password` = {$passwordValue}, `updated_at` = NOW() WHERE `id` = {$userId}");

        return true;
    }

    public function canResetPassword($siteId, $email, $secret)
    {
        $site = $this->getDb()->quote($siteId);
        $emailValue = $this->getDb()->quote($email);
        $secretValue = $this->getDb()->quote($secret);

        return $this->getDb()->query("SELECT 
                                            COUNT(*) 
                                        FROM `users`
                                        WHERE 
                                            `site_id` = {$site} AND 
                                            `login` = {$emailValue} AND
                                            `restore_hash` = {$secretValue} 
                                        LIMIT 1")->fetchColumn() > 0;
    }

    //@TODO: add transactions support
    public function resetPassword($siteId, $email, $secret, $newPassword, $newPasswordConfirm)
    {
        if ($newPassword !== $newPasswordConfirm) {
            throw new PasswordsNotEqualException("Users passwords are not equal!");
        }

        if (strlen($newPassword) < 6) {
            throw new PasswordTooShortException("User password is too short!");
        }

        $password = $this->getDb()->quote($this->getPasswordHash($newPassword));
        $emailValue = $this->getDb()->quote($email);
        $siteValue = $this->getDb()->quote($siteId);
        $secretValue = $this->getDb()->quote($secret);

        $isChanged = $this->getDb()->exec("UPDATE `users` SET `restore_hash` = '', `password` = {$password}, `updated_at` = NOW() WHERE `site_id` = {$siteValue} AND `login` = {$emailValue} AND `restore_hash` = {$secretValue}") > 0;

        if ($isChanged) {
            $this->getUsersNotesProcessor()->accountCustomPasswordSaved($this->getUserId($siteId, $email));
        }

        return $isChanged;
    }

    public function unlockAccount($siteId, $email, $secret)
    {
        $site = $this->getDb()->quote($siteId);
        $emailValue = $this->getDb()->quote($email);
        $secretValue = $this->getDb()->quote($secret);

        $userId = $this->getDb()->query("SELECT * FROM `users` WHERE `site_id` = {$site} AND `login` = {$emailValue} AND `locked` = 1 AND `unlock_hash` = {$secretValue} LIMIT 1")->fetchColumn();

        if ($this->getDb()->exec("UPDATE `users` SET `locked` = 0, `unlock_hash` = '', `updated_at` = NOW() WHERE `id` = {$userId} AND `locked` = 1 AND `unlock_hash` = {$secretValue} LIMIT 1") > 0) {
            $this->getUsersNotesProcessor()->accountUnlocked($userId);
            return true;
        }

        return false;
    }

    private function getLoginAttemptsCount($id)
    {
        $userId = intval($id);

        return $this->getDb()->query("SELECT COUNT(*) FROM `users_auth_attempts` WHERE `user_id` = {$userId} AND NOW() - `created_at` < {$this->loginAttemptsPeriod}")->fetchColumn();
    }

    private function addLoginAttempt($id)
    {
        $userId = intval($id);
        return $this->getDb()->exec("INSERT INTO `users_auth_attempts` SET `user_id` = {$userId}");
    }

    private function removeLoginAttempts($id)
    {
        $userId = intval($id);
        return $this->getDb()->exec("DELETE FROM `users_auth_attempts` WHERE `user_id` = {$userId}");
    }

    private function incFailAttempts($siteId, $data, $environment)
    {
        if ($this->getLoginAttemptsCount($data['id']) >= $this->loginAttempts - 1) {
            $this->lockWithHash($siteId, $data['id'], $data['login']);
            if ($environment['from'] == 'ControlPanel') {
                $this->getUsersNotesProcessor()->accountLocked($data['id'], $this->loginAttempts, $environment['ip'], $environment['userAgent']);
            } elseif ($environment['from'] == 'MobileApplication') {
                $this->getUsersNotesProcessor()->accountLockedMobileApplication($data['id'], $this->loginAttempts, $environment['platform']);
            }

            throw new UserLockedException("User account is locked out!");
        }

        $this->addLoginAttempt($data['id']);
    }

    //@TODO: add transactions support
    public function lockWithHash($siteId, $id, $email)
    {
        $secret = $this->getRandomString();

        $userId = $this->db->quote($id);
        $secretValue = $this->db->quote($secret);

        $this->db->exec("UPDATE `users` SET `unlock_hash` = {$secretValue}, `locked` = 1, `updated_at` = NOW() WHERE `id` = {$userId}");

        $unlockAccountUrl = GlobalSettings::getUnlockAccountPageUrl($siteId, $email, $secret);
        $this->getSender()
                ->setUserId($id)
                ->sendUnlockPassword($email, $unlockAccountUrl);

        $this->removeLoginAttempts($id);
        return true;
    }

    private function getRandomString($salt = '')
    {
        return md5(__CLASS__ . microtime() . $salt);
    }

    public function isUser($siteId, $email)
    {
        $escapedSiteId = $this->db->quote($siteId);
        $escapedEmail = $this->getDb()->quote($email);
        return $this->getDb()->query("SELECT COUNT(*) FROM `users` WHERE `site_id` = {$escapedSiteId} AND `login` = {$escapedEmail} LIMIT 1")->fetchColumn() > 0;
    }

    public function getUserData($siteId, $email)
    {
        $escapedSite = $this->db->quote($siteId);
        $escapedEmail = $this->db->quote($email);

        return $this->db->query("SELECT
                                        `id`,
                                        `login`,
                                        `password`,
                                        `locale`,
                                        `records_per_page`,
                                        `email_confirmed`,
                                        `locked`
                                    FROM `users`
                                    WHERE 
                                        `site_id` = {$escapedSite} AND
                                        `login` = {$escapedEmail}
                                    LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    }

    public function getUserEmail($userId)
    {
        $escapedUserId = $this->db->quote($userId);

        return $this->db->query("SELECT `login` FROM `users` ON `id` = {$escapedUserId} LIMIT 1")->fetchColumn();
    }

    public function getUserId($siteId, $email)
    {
        $escapedSite = $this->db->quote($siteId);
        $escapedEmail = $this->db->quote($email);

        return $this->db->query("SELECT
                                        `id` 
                                    FROM `users`
                                    WHERE 
                                        `site_id` = {$escapedSite} AND
                                        `login` = {$escapedEmail}
                                    LIMIT 1")->fetchColumn();
    }

    public function getUserDataById($siteId, $id)
    {
        $site = $this->db->quote($siteId);
        $userId = $this->db->quote($id);

        $data = $this->db->query("SELECT
                                        `id`,
                                        `login`,
                                        `locale`,
                                        `records_per_page`,
                                        `email_confirmed`
                                    FROM `users`
                                    WHERE
                                        `site_id` = {$site} AND
                                        `id` = {$userId}
                                    LIMIT 1")->fetch(PDO::FETCH_ASSOC);

        if ($data == false) {
            throw new UserNotFoundException("User not found!");
        }

        $data['options'] = $this->getUserOptions($data['id'], array(UserOptionRecord::SCOPE_GLOBAL, UserOptionRecord::SCOPE_CONTROL_PANEL));

        return $data;
    }

    public function logAuth($userId, $timezone = '', $environment = array())
    {
        $userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';

        $info = get_browser($userAgent);

        $userAuthLog = new UserAuthLogRecord($this->db);

        $ip = IP::getRealIP();

        $userAuthLog->setUserId($userId)
                ->setIp($ip)
                ->setCountry(IP::getCountry($ip))
                ->setTimezone($timezone)
                ->setFullInfo(@json_encode($info))
                ->setUserAgent($userAgent);

        if (isset($info->browser, $info->version)) {
            $userAuthLog->setBrowser($info->browser)
                    ->setBrowserVersion($info->version);
        }

        if (isset($info->platform, $info->platform_version)) {
            $userAuthLog->setPlatform($info->platform)
                    ->setPlatformVersion($info->platform_version);
        }

        if (isset($info->ismobiledevice)) {
            $userAuthLog->setMobile($info->ismobiledevice);
        }

        if (isset($info->istablet)) {
            $userAuthLog->setTablet($info->istablet);
        }

        $userAuthLog->save();

        $usersNotes = $this->getUsersNotesProcessor();

        if (isset($environment['from']) && $environment['from'] == 'MobileApplication') {
            $usersNotes->accountEnteredMobileApplication($userAuthLog->getId(), $userId, $environment['platform']);
        } else {
            $usersNotes->accountEntered($userAuthLog->getId(), $userId);
        }
    }

    public function lock($id)
    {
        $userId = $this->db->quote($id);
        return $this->db->exec("UPDATE `users` SET `unlock_hash` = '', `locked` = 1, `updated_at` = NOW() WHERE `id` = {$userId}");
    }

    public function unlock($id)
    {
        $userId = $this->db->quote($id);
        return $this->db->exec("UPDATE `users` SET `unlock_hash` = '', `locked` = 0, `updated_at` = NOW() WHERE `id` = {$userId}");
    }

    //@TODO: add transactions support
    public function createUser($siteId, $email, $name = '', $sendMail = true)
    {
        if ($this->isUser($siteId, $email)) {
            throw new UserAlreadyExistsException("User with this login already exists on this site!");
        }

        $password = substr($this->getRandomString(), 0, 8);
        $emailConfirmHash = $this->getRandomString('confirm');

        $userRecord = new UserRecord($this->db);
        $userRecord->setSiteId($siteId)
                ->setLogin($email)
                ->setName($name)
                ->setPassword($this->getPasswordHash($password))
                ->setEmailConfirmHash($emailConfirmHash)
                ->save();

        if ($sendMail)
            $this->getSender()
                    ->setUserId($userRecord->getId())
                    ->sendRegistrationSuccessWithPassword($email, $email, $password);

        return $userRecord->getId();
    }

    public function updateUserPassword($id)
    {
        $password = substr($this->getRandomString(), 0, 8);
        $emailConfirmHash = $this->getRandomString('confirm');
        // $this->setUserPassword($id, $password);
        $userRecord = new UserRecord($this->db);
        $userRecord->load($id);
        $userRecord->setPassword($this->getPasswordHash($password))
                ->setEmailConfirmHash($emailConfirmHash)
                ->save();

        $this->getSender()
                ->setUserId($userRecord->getId())
                ->sendRegistrationSuccessWithPassword($userRecord->getLogin(), $userRecord->getLogin(), $password);

        return $userRecord->getId();
    }

    // affiliates
    public function getAffiliateId($affId)
    {
        if (empty($affId))
            return false;
        $affId = $this->db->quote($affId);

        $data = $this->db->query("SELECT
                                        *
                                    FROM `affiliates`
                                    WHERE
                                        `id` = {$affId}
                                    LIMIT 1")->fetch(PDO::FETCH_ASSOC);

        return $data;
    }

    // FreeTrial
    public function createUserFreeTrial($siteId, $email, $name)
    {
        if ($this->isUser($siteId, $email)) {
            throw new UserAlreadyExistsException("User with this login already exists on this site!");
        }

        $password = substr($this->getRandomString(), 0, 8);
        $emailConfirmHash = $this->getRandomString('confirm');

        $userRecord = new UserRecord($this->db);
        $userRecord->setSiteId($siteId)
                ->setLogin($email)
                ->setName($name)
                ->setPassword($this->getPasswordHash($password))
                ->setEmailConfirmHash($emailConfirmHash)
                ->save();

        $this->getSender()
                ->setUserId($userRecord->getId())
                ->sendFreeTrialWelcome($email, $email, $password);

        return $userRecord->getId();
    }

    public static function buildDirectLoginHash($siteId, $userId, $adminId, $salt)
    {
        return md5($siteId . $salt . $userId . $salt . $adminId . $salt);
    }

    public function getDirectLoginUserData($siteId, $userId, $adminId, $supportMode, $hash, $salt)
    {
        if ($this->buildDirectLoginHash($siteId, $userId, $adminId, $salt) !== $hash) {
            throw new DirectLoginException("Invalid hash!");
        }

        if (($data = $this->getUserDataById($siteId, $userId)) === false) {
            throw new DirectLoginException("User not found!");
        }

        $data['options'] = $this->getUserOptions($userId, array(UserOptionRecord::SCOPE_GLOBAL, UserOptionRecord::SCOPE_CONTROL_PANEL));

        $data['admin_id'] = $adminId;

        if ($supportMode) {
            $data['support_mode'] = 1;
        }

        $usersNotes = new UsersNotes($this->db, $userId, $adminId);
        $usersNotes->accountEnteredAdmin($supportMode);

        return $data;
    }

    public function deleteUser($id)
    {
        $userId = $this->db->quote($id);

        $this->db->beginTransaction();
        $this->db->exec("DELETE FROM `orders_history` WHERE `order_id` IN (SELECT id FROM `orders` WHERE `user_id` = {$userId})");
        $this->db->exec("DELETE FROM `orders_payments_products` WHERE `order_payment_id` IN (SELECT id FROM `orders_payments` WHERE `order_id` IN (SELECT id FROM `orders` WHERE `user_id` = {$userId}))");
        $this->db->exec("DELETE FROM `orders_payments` WHERE `order_id` IN (SELECT id FROM `orders` WHERE `user_id` = {$userId})");
        $this->db->exec("DELETE FROM `codes` WHERE license_id IN (SELECT `id` FROM `licenses` WHERE `order_product_id` IN (SELECT `id` FROM `orders_products` WHERE `order_id` IN (SELECT id FROM `orders` WHERE `user_id` = {$userId})))");
        $this->db->exec("DELETE FROM `codes` WHERE `user_id` = {$userId}");
        $this->db->exec("DELETE FROM `subscriptions` WHERE license_id IN (SELECT `id` FROM `licenses` WHERE `order_product_id` IN (SELECT `id` FROM `orders_products` WHERE `order_id` IN (SELECT id FROM `orders` WHERE `user_id` = {$userId})))");
        $this->db->exec("DELETE FROM `licenses` WHERE `order_product_id` IN (SELECT `id` FROM `orders_products` WHERE `order_id` IN (SELECT id FROM `orders` WHERE `user_id` = {$userId}))");
        $this->db->exec("DELETE FROM `licenses` WHERE `user_id` = {$userId}");
        $this->db->exec("DELETE FROM `orders_products` WHERE `order_id` IN (SELECT id FROM `orders` WHERE `user_id` = {$userId})");
        $this->db->exec("DELETE FROM `orders` WHERE `user_id` = {$userId}");
        $this->db->exec("DELETE FROM `devices_icloud` WHERE `dev_id` IN (SELECT `id` FROM `devices` WHERE `user_id` = {$userId})");
        $this->db->exec("DELETE FROM `devices_limitations` WHERE `device_id` IN (SELECT `id` FROM `devices` WHERE `user_id` = {$userId})");
        $this->db->exec("DELETE FROM `devices` WHERE `user_id` = {$userId}");
        $this->db->exec("DELETE FROM `users_auth_attempts` WHERE `user_id` = {$userId}");
        $this->db->exec("DELETE FROM `users_auth_log` WHERE `user_id` = {$userId}");
        $this->db->exec("DELETE FROM `users_notes` WHERE `user_id` = {$userId}");
        $this->db->exec("DELETE FROM `users_options` WHERE `user_id` = {$userId}");
        $this->db->exec("DELETE FROM `users_system_notes` WHERE `user_id` = {$userId}");
        $this->db->exec("DELETE FROM `users` WHERE `id` = {$userId}");
        $this->db->commit();
    }

    public function getSessionManager()
    {
        $sessionManager = new SessionsManager($this->db);
        
        return $sessionManager->setUsersManager($this);
    }

    /**
     * 
     * @param int $id
     * @return UserRecord
     */
    public function getUser($id = null)
    {
        $user = new UserRecord($this->db);

        if (isset($id)) {
            $user->load($id);
        }

        return $user;
    }

    public static function registerListeners(PDO $pdo)
    {
        if (self::$listenersRegistered) {
            return;
        }

        $manager = EventManager::getInstance();

        $manager->on('email-sended', function($data) use ($pdo) {
            if (isset($data['userId'])) {
                self::logUserEmailSended($pdo, $data['userId'], $data['type']);
            }
        });

        $jiraLogger = new JiraLogger($pdo);
        $jiraLogger->registerListeners();

        self::$listenersRegistered = true;
    }

    private static function logUserEmailSended(PDO $pdo, $userId, $type)
    {
        $escapedUserId = $pdo->quote($userId);
        $escapedType = $pdo->quote($type);

        $pdo->exec("INSERT INTO `users_emails_log` SET `user_id` = {$escapedUserId}, `type` = {$escapedType}");
    }

}
