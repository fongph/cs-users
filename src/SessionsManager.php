<?php

namespace CS\Users;

use CS\Users\UsersManager;
use CS\Mail\MailSender;
use CS\Mail\Processor\RemoteProcessor;
use CS\Settings\GlobalSettings;

/**
 * Description of SessionsManager
 *
 * @author root
 */
class SessionsManager {

    const DEFAULT_LIFE_TIME = 86400;
    const MAX_ACTIVE_SESSIONS_PER_ACCOUNT = 16;

    private $pdo;
    private $usersManager;

    private function getToken($email)
    {
        return md5($email . rand(0, 9999999) . time());
    }

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * 
     * @todo remove cyclic links
     * @return type
     * @throws InvalidSenderObjectException
     */
    private function getUsersManager()
    {
        if (!($this->usersManager instanceof UsersManager)) {
            throw new Exception("UsersManger must be defined");
        }

        return $this->usersManager;
    }

    public function setUsersManager(UsersManager $usersManager)
    {
        $this->usersManager = $usersManager;

        return $this;
    }

    public function getSessionUserId($token, $userAgent)
    {
        $userAgentString = $this->pdo->quote($userAgent);
        $lifeTimeValue = $this->pdo->quote(time());
        $sessionId = $this->pdo->quote($token);

        return $this->pdo->query("SELECT `user_id` FROM `users_auth_sessions` WHERE `session_id` = {$sessionId} AND `user_agent` = {$userAgentString} AND `lifetime` > {$lifeTimeValue}")->fetchColumn();
    }

    public function create($siteId, $email, $password, $userAgent, $lifeTime = self::DEFAULT_LIFE_TIME)
    {
        $environment = array(
            'from' => 'MobileApplication',
            'platform' => $userAgent
        );

        $data = $this->getUsersManager()->login($siteId, $email, $password, '', $environment);

        $token = $this->getToken($email);
        $userId = $this->pdo->quote($data['id']);
        $userAgentString = $this->pdo->quote($userAgent);
        $lifeTimeValue = $this->pdo->quote($lifeTime + time());
        $sessionId = $this->pdo->quote($token);

        $this->pdo->exec("INSERT INTO `users_auth_sessions` SET `user_id` = {$userId}, `session_id` = {$sessionId}, `user_agent` = {$userAgentString}, `lifetime` = {$lifeTimeValue}");
        return $token;
    }

    public function getActiveSessionsCount($userId)
    {
        $escapedUserId = $this->pdo->quote($userId);

        return $this->pdo->query("SELECT COUNT(*) FROM `users_auth_sessions` WHERE `user_id` = {$escapedUserId}")->fetchColumn();
    }

}
