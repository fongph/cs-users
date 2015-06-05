<?php

namespace CS\Users;

use PDO,
    Exception,
    CS\Models\User\UsersSystemNotes\UsersSystemNoteRecord;

class UsersNotes
{

    const TYPE_SYSTEM = 'sys';
    const TYPE_AUTH = 'auth';

    /**
     *
     * @var PDO
     */
    private $db;

    /**
     *
     * @var int
     */
    private $userId;

    /**
     *
     * @var int
     */
    private $adminId;
    protected $availableTypes = array(
        self::TYPE_AUTH,
        self::TYPE_SYSTEM,
    );

    /**
     * 
     * @todo set $userId required by default
     * @param PDO $db
     * @param type $userId
     * @param type $adminId
     */
    public function __construct(PDO $db, $userId = null, $adminId = null)
    {
        $this->db = $db;
        $this->userId = $userId;
        $this->adminId = $adminId;
    }

    private function getUserId($value)
    {
        if ($value !== null) {
            return $value;
        }
        
        if ($this->userId === null) {
            throw new Exception("UserId required!");
        }
        
        return $this->userId;
    }
    
    private function getAdminId($value)
    {
        if ($value !== null) {
            return $value;
        }
        
        return $this->adminId;
    }

    public function deviceAdded($deviceId, $userId = null)
    {
        $realUserId = $this->getUserId($userId);
        
        $usersSystemNote = new UsersSystemNoteRecord($this->db);
        $usersSystemNote->setType(UsersSystemNoteRecord::TYPE_SYSTEM)
                ->setUserId($realUserId)
                ->setContent("New device #{$deviceId} added");
                
        if ($this->adminId !== null) {
            $usersSystemNote->setAdminId($this->adminId);
        }

        $usersSystemNote->save();     
    }

    public function deviceDeleted($deviceId, $userId = null)
    {
        $realUserId = $this->getUserId($userId);
        
        $usersSystemNote = new UsersSystemNoteRecord($this->db);
        $usersSystemNote->setType(UsersSystemNoteRecord::TYPE_SYSTEM)
                ->setUserId($realUserId)
                ->setContent("Device #{$deviceId} deleted");

        if ($this->adminId !== null) {
            $usersSystemNote->setAdminId($this->adminId);
        }

        $usersSystemNote->save();
    }

    public function deviceLimitsUpdated($deviceId, $userId = null)
    {
        $realUserId = $this->getUserId($userId);
        
        $usersSystemNote = new UsersSystemNoteRecord($this->db);
        $usersSystemNote->setType(UsersSystemNoteRecord::TYPE_SYSTEM)
                ->setUserId($realUserId)
                ->setContent("Device #{$deviceId} limits updated");

        if ($this->adminId !== null) {
            $usersSystemNote->setAdminId($this->adminId);
        }

        $usersSystemNote->save();
    }
    
    public function licenseAssigned($licenseId, $deviceId, $userId = null)
    {
        $realUserId = $this->getUserId($userId);
        
        $usersSystemNote = new UsersSystemNoteRecord($this->db);
        $usersSystemNote->setType(UsersSystemNoteRecord::TYPE_SYSTEM)
                ->setUserId($realUserId)
                ->setContent("Subscription #{$licenseId} assigned to device #{$deviceId}");

        if ($this->adminId !== null) {
            $usersSystemNote->setAdminId($this->adminId);
        }

        $usersSystemNote->save();
    }

    public function licenseAdded($licenseId, $userId = null)
    {
        $realUserId = $this->getUserId($userId);
        
        $usersSystemNote = new UsersSystemNoteRecord($this->db);
        $usersSystemNote->setType(UsersSystemNoteRecord::TYPE_SYSTEM)
                ->setUserId($realUserId)
                ->setContent("Subscription #{$licenseId} added");

        if ($this->adminId !== null) {
            $usersSystemNote->setAdminId($this->adminId);
        }

        $usersSystemNote->save();
    }
    
    public function licenseRebilled($licenseId, $userId = null)
    {
        $realUserId = $this->getUserId($userId);
        
        $usersSystemNote = new UsersSystemNoteRecord($this->db);
        $usersSystemNote->setType(UsersSystemNoteRecord::TYPE_SYSTEM)
                ->setUserId($realUserId)
                ->setContent("Subscription #{$licenseId} rebilled");

        if ($this->adminId !== null) {
            $usersSystemNote->setAdminId($this->adminId);
        }

        $usersSystemNote->save();
    }

    public function licenseExpired($licenseId, $userId = null)
    {
        $realUserId = $this->getUserId($userId);
        
        $usersSystemNote = new UsersSystemNoteRecord($this->db);
        $usersSystemNote->setType(UsersSystemNoteRecord::TYPE_SYSTEM)
                ->setUserId($realUserId)
                ->setContent("Subscription #{$licenseId} expired")
                ->save();
    }

    public function licenseDropped($licenseId, $deviceId, $userId = null)
    {
        $realUserId = $this->getUserId($userId);
        
        $usersSystemNote = new UsersSystemNoteRecord($this->db);
        $usersSystemNote->setType(UsersSystemNoteRecord::TYPE_SYSTEM)
                ->setUserId($realUserId)
                ->setContent("Subscription #{$licenseId} dropped from device #{$deviceId}");

        if ($this->adminId !== null) {
            $usersSystemNote->setAdminId($this->adminId);
        }

        $usersSystemNote->save();
    }
    
    public function licenseUpgraded($deviceId, $oldLicenseId, $newLicenseId, $userId = null)
    {
        $realUserId = $this->getUserId($userId);

        $usersSystemNote = new UsersSystemNoteRecord($this->db);
        $usersSystemNote
            ->setType(UsersSystemNoteRecord::TYPE_SYSTEM)
            ->setUserId($realUserId)
            ->setContent("Subscription #{$oldLicenseId} upgraded to #{$newLicenseId} for device #{$deviceId}");

        if ($this->adminId !== null) {
            $usersSystemNote->setAdminId($this->adminId);
        }

        $usersSystemNote->save();
    }

    public function licenseUnAssigned($licenseId, $deviceId, $userId = null)
    {
        $realUserId = $this->getUserId($userId);
        
        $usersSystemNote = new UsersSystemNoteRecord($this->db);
        $usersSystemNote->setType(UsersSystemNoteRecord::TYPE_SYSTEM)
                ->setUserId($realUserId)
                ->setContent("Subscription #{$licenseId} unassigned from device #{$deviceId}");

        if ($this->adminId !== null) {
            $usersSystemNote->setAdminId($this->adminId);
        }

        $usersSystemNote->save();
    }

    public function accountEntered($authLogId, $userId = null)
    {
        $realUserId = $this->getUserId($userId);
        
        $usersSystemNote = new UsersSystemNoteRecord($this->db);
        $usersSystemNote->setType(UsersSystemNoteRecord::TYPE_AUTH)
                ->setUserId($realUserId)
                ->setJoinId($authLogId);

        if ($this->adminId !== null) {
            $usersSystemNote->setAdminId($this->adminId);
        }

        $usersSystemNote->save();
    }
    
    public function accountEnteredAdmin($supportMode = false, $userId = null)
    {
        $realUserId = $this->getUserId($userId);
        
        $usersSystemNote = new UsersSystemNoteRecord($this->db);
        $usersSystemNote->setType(UsersSystemNoteRecord::TYPE_SYSTEM)
                ->setUserId($realUserId);
        
        if ($supportMode) {
            $usersSystemNote->setContent("Login under account as Support");
        } else {
            $usersSystemNote->setContent("Login under account as Client");
        }

        if ($this->adminId !== null) {
            $usersSystemNote->setAdminId($this->adminId);
        }

        $usersSystemNote->save();
    }

    public function accountLocked($userId = null)
    {
        $realUserId = $this->getUserId($userId);
        
        $usersSystemNote = new UsersSystemNoteRecord($this->db);
        $usersSystemNote->setType(UsersSystemNoteRecord::TYPE_SYSTEM)
                ->setUserId($realUserId)
                ->setContent("Account locked");

        if ($this->adminId !== null) {
            $usersSystemNote->setAdminId($this->adminId);
        }

        $usersSystemNote->save();
    }

    public function accountUnlocked($userId = null)
    {
        $realUserId = $this->getUserId($userId);
        
        $usersSystemNote = new UsersSystemNoteRecord($this->db);
        $usersSystemNote->setType(UsersSystemNoteRecord::TYPE_SYSTEM)
                ->setUserId($realUserId)
                ->setContent("Account unlocked");

        if ($this->adminId !== null) {
            $usersSystemNote->setAdminId($this->adminId);
        }

        $usersSystemNote->save();
    }

    public function accountRestored($userId = null)
    {
        $realUserId = $this->getUserId($userId);
        
        $usersSystemNote = new UsersSystemNoteRecord($this->db);
        $usersSystemNote->setType(UsersSystemNoteRecord::TYPE_SYSTEM)
                ->setUserId($realUserId)
                ->setContent("Restore email successfully sent");

        if ($this->adminId !== null) {
            $usersSystemNote->setAdminId($this->adminId);
        }

        $usersSystemNote->save();
    }

    public function accountCustomPasswordSaved($userId = null)
    {
        $realUserId = $this->getUserId($userId);
        
        $usersSystemNote = new UsersSystemNoteRecord($this->db);
        $usersSystemNote->setType(UsersSystemNoteRecord::TYPE_SYSTEM)
                ->setUserId($realUserId)
                ->setContent("Custom password successfully saved");

        if ($this->adminId !== null) {
            $usersSystemNote->setAdminId($this->adminId);
        }

        $usersSystemNote->save();
    }
    
    public function supportTicketSent($ticketId, $userId = null)
    {
        $realUserId = $this->getUserId($userId);

        $usersSystemNote = new UsersSystemNoteRecord($this->db);
        $usersSystemNote->setType(UsersSystemNoteRecord::TYPE_SYSTEM)
            ->setUserId($realUserId)
            ->setContent("Support Ticket #{$ticketId} has been successfully sent");

        $usersSystemNote->save();
    }
    
    public function licenseSubscriptionAutoRebillTaskAdded($licenseId, $userId = null, $adminId = null) {
        $realUserId = $this->getUserId($userId);
        $realAdminId = $this->getAdminId($adminId);
        
        $usersSystemNote = new UsersSystemNoteRecord($this->db);
        $usersSystemNote->setType(UsersSystemNoteRecord::TYPE_SYSTEM)
                ->setUserId($realUserId)
                ->setAdminId($realAdminId)
                ->setContent("Autorebill status change for subscription #{$licenseId} queued");

        $usersSystemNote->save();
    }
    
    public function licenseSubscriptionAutoRebillEnabled($licenseId, $userId = null) {
        $realUserId = $this->getUserId($userId);
        
        $usersSystemNote = new UsersSystemNoteRecord($this->db);
        $usersSystemNote->setType(UsersSystemNoteRecord::TYPE_SYSTEM)
                ->setUserId($realUserId)
                ->setContent("Autorebill for subscription #{$licenseId} was ENABLED");

        if ($this->adminId !== null) {
            $usersSystemNote->setAdminId($this->adminId);
        }

        $usersSystemNote->save();
    }
    
    public function licenseSubscriptionAutoRebillDisabled($licenseId, $userId = null) {
        $realUserId = $this->getUserId($userId);
        
        $usersSystemNote = new UsersSystemNoteRecord($this->db);
        $usersSystemNote->setType(UsersSystemNoteRecord::TYPE_SYSTEM)
                ->setUserId($realUserId)
                ->setContent("Autorebill for subscription #{$licenseId} was DISABLED");

        if ($this->adminId !== null) {
            $usersSystemNote->setAdminId($this->adminId);
        }

        $usersSystemNote->save();
    }
    
    /**
     * 
     * @deprecated
     * @param type $licenseId
     * @param type $userId
     */
    public function licenseSubscriptionAutorenewOff($licenseId, $userId = null) {
        $realUserId = $this->getUserId($userId);
        
        $usersSystemNote = new UsersSystemNoteRecord($this->db);
        $usersSystemNote->setType(UsersSystemNoteRecord::TYPE_SYSTEM)
                ->setUserId($realUserId)
                ->setContent("Subscription #{$licenseId} autorenew status changed to Off!");

        if ($this->adminId !== null) {
            $usersSystemNote->setAdminId($this->adminId);
        }

        $usersSystemNote->save();
    }
    
    /**
     * 
     * @deprecated
     * @param type $licenseId
     * @param type $userId
     */
    public function licenseSubscriptionAutorenewOn($licenseId, $userId = null) {
        $realUserId = $this->getUserId($userId);
        
        $usersSystemNote = new UsersSystemNoteRecord($this->db);
        $usersSystemNote->setType(UsersSystemNoteRecord::TYPE_SYSTEM)
                ->setUserId($realUserId)
                ->setContent("Subscription #{$licenseId} autorenew status changed to On!");

        if ($this->adminId !== null) {
            $usersSystemNote->setAdminId($this->adminId);
        }

        $usersSystemNote->save();
    }

    public function addSystemNote($userId, $type = self::TYPE_SYSTEM, $adminId = null, $joinId = null, $content = '')
    {
        switch (true) {
            case!in_array($type, $this->availableTypes):
                throw new WrongSystemNoteType;

            case $type == self::TYPE_SYSTEM && !is_null($joinId):
            case $type != self::TYPE_AUTH && (int) $joinId:
                throw new WrongSystemNoteParams;
        }

        $query = $this->db->prepare("
            INSERT INTO users_system_notes
            SET user_id = :user_id,
                admin_id = :admin_id,
                `type` = :type,
                join_id = :join_id,
                content = :message");
        $query->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $query->bindParam(':type', $type, PDO::PARAM_STR);
        $query->bindParam(':admin_id', $adminId, $adminId ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $query->bindParam(':join_id', $joinId, $joinId ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $query->bindParam(':message', $content, PDO::PARAM_STR);

        return $query->execute();
    }

    public function getSystemNotes($userId, $params = array())
    {
        $limit = "";
        if (isset($params['iDisplayStart'])) {
            $limit = "LIMIT " . intval($params['iDisplayStart']) . ", " . intval($params['iDisplayLength']);
        }

        $userId = (int) $userId;
        $records = $this->db->query("
            select SQL_CALC_FOUND_ROWS 
                unix_timestamp(l.date) timestamp,
                admin.email actor,
                l.type type,
                l.content description,
                
                auth.ip, auth.mobile, auth.tablet, auth.browser, auth.browser_version, auth.platform, auth.platform_version, auth.country
            
            from users_system_notes l
            
            left join admin_users admin on l.admin_id is not null and admin.id = l.admin_id
            
            left join users_auth_log auth on l.`type` = 'auth' and l.join_id = auth.id and l.user_id = auth.user_id
            
            where l.user_id = {$userId}
            group by l.id
            order by date desc, l.id desc " . $limit)->fetchAll(PDO::FETCH_ASSOC);

        $total = $this->db->query("SELECT FOUND_ROWS()")->fetchColumn();

        return array(
            "sEcho" => intval($params['sEcho']),
            "iTotalRecords" => $total,
            "iTotalDisplayRecords" => $total,
            "aaData" => $records
        );
    }

}

class WrongSystemNoteType extends Exception
{
    
}

class WrongSystemNoteParams extends Exception
{
    
}
