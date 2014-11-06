<?php

namespace CS\Users;

use PDO,
    CS\Models\User\UserRecord;

/**
 * Description of Manager
 *
 * @author root
 */
class Manager
{

    /**
     * Database connection
     * 
     * @var PDO
     */
    protected $db;

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
        $data = $this->getUserData($siteId, $email);

        if ($data == false) {
            throw new UserNotFoundException("User not found!");
        }

        if (!verifyPassword($data['password'], $password)) {
            throw new InvalidPasswordException("Invalid password!");
        }

        if (!$data['active']) {
            throw new UserNotActiveException("User is not active!");
        }

        unset($data['active'], $data['password']);

        return $data;
    }

    private function getUserData($siteId, $email)
    {
        $escapedSite = $this->db->quote($siteId);
        $escapedEmail = $this->db->quote($email);

        return $this->db->query("SELECT
                                            `login`,
                                            `password`,
                                            `email_confirmed`,
                                            `active`
                                        FROM `users`
                                        WHERE 
                                            `site_id` = {$escapedSite} AND
                                            `login` = {$escapedEmail}
                                        LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    }

    public function loginById($siteId, $id)
    {
        $escapedSite = $this->db->quote($siteId);
        $userId = $this->db->quote($id);

        return $this->db->query("SELECT
                                            `login`,
                                            `email_confirmed`
                                        FROM `users`
                                        WHERE 
                                            `site_id` = {$escapedSite} AND
                                            `id` = {$userId}
                                        LIMIT 1")->fetch(PDO::FETCH_ASSOC);
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
