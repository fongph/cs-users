<?php

namespace CS\Users;

use PDO,
    IP,
    CS\Settings\GlobalSettings,
    CS\Mail\MailSender,
    CS\Models\User\UserRecord,
    CS\Models\User\AuthLog\UserAuthLogRecord;

/**
 * Description of Manager
 *
 * @author root
 */
class UsersManager
{

    /**
     * Database connection
     * 
     * @var PDO
     */
    protected $db;

    /**
     *
     * @var MailSender 
     */
    protected $sender;
    protected $loginAttempts = 5;
    protected $loginAttemptsPeriod = 300; // 5 min

    public function __construct(\PDO $db)
    {
        $this->db = $db;
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
    }

    public function getSender()
    {
        if (!($this->sender instanceof Sender)) {
            throw new InvalidSenderObjectException("Invalid mail sender object!");
        }

        return $this->sender;
    }

    public function getPasswordHash($password)
    {
        return password_hash($password, PASSWORD_BCRYPT);
    }

    public function verifyPassword($hash, $password)
    {
        return password_verify($password, $hash);
    }

    public function login($siteId, $email, $password)
    {
        if (strlen($password) < 6) {
            throw new PasswordTooShortException("User password is too short!");
        }
        
        $data = $this->getUserData($siteId, $email);

        if ($data == false) {
            throw new UserNotFoundException("User not found!");
        }

        if ($data['locked']) {
            throw new UserLockedException("User account is locked out!");
        }

        if (!$this->verifyPassword($data['password'], $password)) {
            $this->incFailAttempts($siteId, $data);
            throw new InvalidPasswordException("Invalid password!");
        }

        $this->logAuth($data['id']);
        unset($data['locked'], $data['password']);

        return $data;
    }

    //@TODO: add transactions support
    public function lostPassword($siteId, $email)
    {
        if (!$this->isUser($siteId, $email)) {
            throw new UsersEmailNotFoundException();
        }

        $secret = $this->getRandomString();

        $escapedSiteId = $this->getDb()->quote($siteId);
        $escapedEmail = $this->getDb()->quote($email);
        $secretValue = $this->getDb()->quote($secret);

        $this->getDb()->exec("UPDATE `users` SET 
                                    `restore_hash` = {$secretValue}, 
                                    `updated_at` = NOW() 
                                WHERE 
                                    `site_id` = {$escapedSiteId} AND 
                                    `login` = {$escapedEmail}
                                LIMIT 1");



        $restorePasswordUrl = GlobalSettings::getRestorePasswordPageUrl($siteId, $email, $secret);
        $this->getSender()->sendLostPasswordEmail($email, $restorePasswordUrl);

        return true;
    }

    //@TODO: add transactions support
    public function resetPassword($email, $newPassword, $newPasswordConfirm)
    {
        if ($newPassword !== $newPasswordConfirm) {
            throw new PasswordsNotEqualException("Users passwords are not equal!");
        }

        if (strlen($newPassword) < 6) {
            throw new PasswordTooShortException("User password is too short!");
        }

        $password = $this->getDb()->quote($this->getHash($newPassword));
        $emailValue = $this->getDb()->quote($email);
        $this->getDb()->exec("UPDATE `users` SET `restore_hash` = '', `password` = {$password}, `updated_at` = NOW() WHERE `login` = {$emailValue}");
        
        return true;
    }

    private function getLoginAttemptsCount($id)
    {
        $userId = intval($id);

        return $this->getDb()->query("SELECT COUNT(*) FROM `users_auth_attempts` WHERE `user_id` = {$userId} AND NOW() - `created_at` < {$this->loginAttemptsPeriod}")->fetchColumn(PDO::FETCH_ASSOC);
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

    private function incFailAttempts($siteId, $data)
    {
        if ($this->getLoginAttemptsCount($data['id']) >= $this->loginAttempts - 1) {
            $this->lockWithHash($siteId, $data['id'], $data['login']);
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
        $this->getSender()->sendUnlockPassword($email, $unlockAccountUrl);

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

    private function getUserData($siteId, $email)
    {
        $escapedSite = $this->db->quote($siteId);
        $escapedEmail = $this->db->quote($email);

        return $this->db->query("SELECT
                                        `id`,
                                        `login`,
                                        `password`,
                                        `email_confirmed`,
                                        `locked`
                                    FROM `users`
                                    WHERE 
                                        `site_id` = {$escapedSite} AND
                                        `login` = {$escapedEmail}
                                    LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    }

    public function loginById($id)
    {
        $userId = $this->db->quote($id);

        $data = $this->db->query("SELECT
                                        `login`,
                                        `email_confirmed`
                                    FROM `users`
                                    WHERE 
                                        `id` = {$userId}
                                    LIMIT 1")->fetch(PDO::FETCH_ASSOC);

        if ($data == false) {
            throw new UserNotFoundException("User not found!");
        }

        return $data;
    }

    private function logAuth($id)
    {
        $info = get_browser();

        $userAuthLog = new UserAuthLogRecord($this->db);

        $ip = IP::getRealIP();

        $userAuthLog->setUserId(intval($id))
                ->setIp($ip)
                ->setCountry(IP::getCountry($ip))
                ->setFullInfo(json_encode($info))
                ->setUserAgent($_SERVER['HTTP_USER_AGENT']);

        if (isset($info->browser, $info->version)) {
            $userAuthLog->setBrowser($info->browser)
                    ->setBrowserVersion($info->version);
        }

        if (isset($info->platform, $info->platform_version)) {
            $userAuthLog->setPlatform($info->platform)
                    ->setPlatformVersion($info->platform_version);
        }

        if (isset($info->ismobiledevice)) {
            $userAuthLog->setMobile($info->ismobiledevice > 0);
        }

        if (isset($info->istablet)) {
            $userAuthLog->setTablet($info->istablet > 0);
        }

        $userAuthLog->save();
    }

    public function lock($id)
    {
        $userId = $this->db->quote($id);
        return $this->db->exec("UPDATE `users` SET `unlock_hash` = '', `locked` = 1, `updated_at` = NOW() WHERE `id` = {$userId}");
    }

    //@TODO: add transactions support
    public function createUser($siteId, $email)
    {
        if ($this->isUser($siteId, $email)) {
            throw new UserAlreadyExistsException("User with this login already exists on this site!");
        }

        $password = substr($this->getRandomString(), 0, 8);
        $emailConfirmHash = $this->getRandomString('confirm');

        $userRecord = new UserRecord($this->db);
        $userRecord->setSiteId($siteId)
                ->setLogin($email)
                ->setPassword($password)
                ->setEmailConfirmHash($emailConfirmHash)
                ->save();


        $confirmEmailUrl = GlobalSettings::getEmailConfirmPageUrl($siteId, $email, $emailConfirmHash);
        $this->getSender()->sendRegistrationSuccessWithPassword($email, $password, $confirmEmailUrl);
        
        return true;
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

}
