<?php

namespace CS\Users;

use PDO,
    EventManager\EventManager,
    Exception,
    CS\Models\User\UsersSystemNotes\UsersSystemNoteRecord;

class UsersNotes
{

    const TYPE_SYSTEM = 'sys';
    const TYPE_AUTH = 'auth';
    const TYPE_APP = 'app';
    const DATE_FORMAT = 'd-m-Y';

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
        self::TYPE_APP,
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

    public function shoppingStarted($userId = null)
    {
        $realUserId = $this->getUserId($userId);

        $usersSystemNote = new UsersSystemNoteRecord($this->db);
        $usersSystemNote->setType(UsersSystemNoteRecord::TYPE_SYSTEM)
                ->setUserId($realUserId)
                ->setContent('User has started shopping');

        if ($this->adminId !== null) {
            $usersSystemNote->setAdminId($this->adminId);
        }

        $usersSystemNote->save();

        $this->emitEvent($usersSystemNote);
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

        $this->emitEvent($usersSystemNote);
    }

    public function deviceDuplicated($deviceId, $accounts, $userId = null)
    {
        $realUserId = $this->getUserId($userId);

        foreach ($accounts as $key => $value) {
            $accounts[$key] = '#' . $value;
        }

        $usersSystemNote = new UsersSystemNoteRecord($this->db);
        $usersSystemNote->setType(UsersSystemNoteRecord::TYPE_SYSTEM)
                ->setUserId($realUserId)
                ->setContent("Device #{$deviceId} was previously connected to accounts: " . implode(', ', $accounts));

        if ($this->adminId !== null) {
            $usersSystemNote->setAdminId($this->adminId);
        }

        $usersSystemNote->save();

        $this->emitEvent($usersSystemNote);
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

        $this->emitEvent($usersSystemNote);
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

        $this->emitEvent($usersSystemNote);
    }

    public function deviceFindMyIphoneConnected($deviceId, $model, $name, $userId = null)
    {
        $realUserId = $this->getUserId($userId);

        $usersSystemNote = new UsersSystemNoteRecord($this->db);
        $usersSystemNote->setType(UsersSystemNoteRecord::TYPE_SYSTEM)
                ->setUserId($realUserId)
                ->setContent("Device #{$deviceId} ({$model}, {$name}) was manually connected to Find My iPhone service");

        if ($this->adminId !== null) {
            $usersSystemNote->setAdminId($this->adminId);
        }

        $usersSystemNote->save();

        $this->emitEvent($usersSystemNote);
    }

    public function deviceFindMyIphoneAutoConnected($deviceId, $model, $name, $userId = null)
    {
        $realUserId = $this->getUserId($userId);

        $usersSystemNote = new UsersSystemNoteRecord($this->db);
        $usersSystemNote->setType(UsersSystemNoteRecord::TYPE_SYSTEM)
                ->setUserId($realUserId)
                ->setContent("Device #{$deviceId} ({$model}, {$name}) was automatically connected to Find My iPhone service");

        if ($this->adminId !== null) {
            $usersSystemNote->setAdminId($this->adminId);
        }

        $usersSystemNote->save();

        $this->emitEvent($usersSystemNote);
    }

    public function deviceFindMyIphoneDisconnected($deviceId, $userId = null)
    {
        $realUserId = $this->getUserId($userId);

        $usersSystemNote = new UsersSystemNoteRecord($this->db);
        $usersSystemNote->setType(UsersSystemNoteRecord::TYPE_SYSTEM)
                ->setUserId($realUserId)
                ->setContent("Device #{$deviceId} was disconnected from Find My iPhone service");

        if ($this->adminId !== null) {
            $usersSystemNote->setAdminId($this->adminId);
        }

        $usersSystemNote->save();

        $this->emitEvent($usersSystemNote);
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

        $this->emitEvent($usersSystemNote);
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

        $this->emitEvent($usersSystemNote);
    }

    public function licenseAddedCustom($licenseId, $name, $lifetime, $userId = null)
    {
        $realUserId = $this->getUserId($userId);

        $date = date(self::DATE_FORMAT, $lifetime);

        $usersSystemNote = new UsersSystemNoteRecord($this->db);
        $usersSystemNote->setType(UsersSystemNoteRecord::TYPE_SYSTEM)
                ->setUserId($realUserId)
                ->setContent("\"{$name}\" subscription #{$licenseId} with expiry date {$date} was added");

        if ($this->adminId !== null) {
            $usersSystemNote->setAdminId($this->adminId);
        }

        $usersSystemNote->save();

        $this->emitEvent($usersSystemNote);
    }

    public function licenseExpirationDateUpdateRequest($licenseId, $userId = null)
    {
        $realUserId = $this->getUserId($userId);

        $usersSystemNote = new UsersSystemNoteRecord($this->db);
        $usersSystemNote->setType(UsersSystemNoteRecord::TYPE_SYSTEM)
                ->setUserId($realUserId)
                ->setContent("Subscription expiration date setup requested for subscription #{$licenseId}");

        if ($this->adminId !== null) {
            $usersSystemNote->setAdminId($this->adminId);
        }

        $usersSystemNote->save();

        $this->emitEvent($usersSystemNote);
    }

    public function licenseUpdated($licenseId, $oldLifetime, $newLifetime, $userId = null)
    {
        $realUserId = $this->getUserId($userId);

        $oldDate = date(self::DATE_FORMAT, $oldLifetime);
        $newDate = date(self::DATE_FORMAT, $newLifetime);

        $usersSystemNote = new UsersSystemNoteRecord($this->db);
        $usersSystemNote->setType(UsersSystemNoteRecord::TYPE_SYSTEM)
                ->setUserId($realUserId)
                ->setContent("Expiry date for subscription #{$licenseId} was changed from {$oldDate} to {$newDate}");

        if ($this->adminId !== null) {
            $usersSystemNote->setAdminId($this->adminId);
        }

        $usersSystemNote->save();

        $this->emitEvent($usersSystemNote);
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

        $this->emitEvent($usersSystemNote);
    }

    public function licenseDiscountOffered($licenseId, $userId = null)
    {
        $realUserId = $this->getUserId($userId);

        $usersSystemNote = new UsersSystemNoteRecord($this->db);
        $usersSystemNote->setType(UsersSystemNoteRecord::TYPE_SYSTEM)
                ->setUserId($realUserId)
                ->setContent("Discount for subscription #{$licenseId} was offered");

        if ($this->adminId !== null) {
            $usersSystemNote->setAdminId($this->adminId);
        }

        $usersSystemNote->save();

        $this->emitEvent($usersSystemNote);
    }

    public function licenseCancelationDiscountAccepted($licenseId, $userId = null)
    {
        $realUserId = $this->getUserId($userId);

        $usersSystemNote = new UsersSystemNoteRecord($this->db);
        $usersSystemNote->setType(UsersSystemNoteRecord::TYPE_SYSTEM)
                ->setUserId($realUserId)
                ->setContent("Discount 20% for subscription #{$licenseId} was accepted");

        if ($this->adminId !== null) {
            $usersSystemNote->setAdminId($this->adminId);
        }

        $usersSystemNote->save();

        $this->emitEvent($usersSystemNote);
    }

    public function licenseExpired($licenseId, $userId = null)
    {
        $realUserId = $this->getUserId($userId);

        $usersSystemNote = new UsersSystemNoteRecord($this->db);
        $usersSystemNote->setType(UsersSystemNoteRecord::TYPE_SYSTEM)
            ->setUserId($realUserId)
            ->setContent("Subscription #{$licenseId} expired")
            ->save();

        $this->emitEvent($usersSystemNote);
    }

    public function licenseFreeDropped($parenLicenceId, $freeLicenseId, $deviceId, $userId = null)
    {
        $realUserId = $this->getUserId($userId);

        $usersSystemNote = new UsersSystemNoteRecord($this->db);
        $usersSystemNote->setType(UsersSystemNoteRecord::TYPE_SYSTEM)
            ->setUserId($realUserId)
            ->setContent("Free Subscription #{$freeLicenseId} (parent subscription #{$parenLicenceId}) dropped from device #{$deviceId}");

        if ($this->adminId !== null) {
            $usersSystemNote->setAdminId($this->adminId);
        }

        $usersSystemNote->save();

        $this->emitEvent($usersSystemNote);
    }

    public function licenseFreeDroppedEmptyDevice($parenLicenceId, $freeLicenseId, $userId = null)
    {
        $realUserId = $this->getUserId($userId);

        $usersSystemNote = new UsersSystemNoteRecord($this->db);
        $usersSystemNote->setType(UsersSystemNoteRecord::TYPE_SYSTEM)
                ->setUserId($realUserId)
                ->setContent("Free Subscription #{$freeLicenseId} (parent subscription #{$parenLicenceId}) dropped");

        if ($this->adminId !== null) {
            $usersSystemNote->setAdminId($this->adminId);
        }

        $usersSystemNote->save();

        $this->emitEvent($usersSystemNote);
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

        $this->emitEvent($usersSystemNote);
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

        $this->emitEvent($usersSystemNote);
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

        $this->emitEvent($usersSystemNote);
    }

    public function licenseRebillPaymentFailed($orderId, $userId = null)
    {
        $realUserId = $this->getUserId($userId);

        $usersSystemNote = new UsersSystemNoteRecord($this->db);
        $usersSystemNote->setType(UsersSystemNoteRecord::TYPE_SYSTEM)
                ->setUserId($realUserId)
                ->setContent("Subscription Payment Failure for order #{$orderId}")
                ->save();

        $this->emitEvent($usersSystemNote);
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
    public function accountEnteredMobileApplication($authLogId, $userId = null, $platform)
    {
        $realUserId = $this->getUserId($userId);
        $usersSystemNote = new UsersSystemNoteRecord($this->db);
        $usersSystemNote->setType(UsersSystemNoteRecord::TYPE_APP)
            ->setUserId($realUserId)
            ->setJoinId($authLogId)
            ->setContent("Login from {$platform}");


        if ($this->adminId !== null) {
            $usersSystemNote->setAdminId($this->adminId);
        }

        $usersSystemNote->save();

        $this->emitEvent($usersSystemNote);
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

    public function accountLocked($userId = null, $countLoginAttempts, $ip, $userAgent)
    {
        $realUserId = $this->getUserId($userId);

        $usersSystemNote = new UsersSystemNoteRecord($this->db);
        $usersSystemNote->setType(UsersSystemNoteRecord::TYPE_SYSTEM)
                ->setUserId($realUserId)
                ->setContent("Account was blocked after {$countLoginAttempts} unsuccessfull login attempts. Last IP: {$ip}, last user agent: {$userAgent} ");

        if ($this->adminId !== null) {
            $usersSystemNote->setAdminId($this->adminId);
        }

        $usersSystemNote->save();

        $this->emitEvent($usersSystemNote);
    }

    public function accountLockedMobileApplication($userId = null, $countLoginAttempts, $platform)
    {
        $realUserId = $this->getUserId($userId);

        $usersSystemNote = new UsersSystemNoteRecord($this->db);
        $usersSystemNote->setType(UsersSystemNoteRecord::TYPE_SYSTEM)
                ->setUserId($realUserId)
                ->setContent("Account was blocked after {$countLoginAttempts} unsuccessfull login attempts from CPapp. Platform: {$platform} ");

        if ($this->adminId !== null) {
            $usersSystemNote->setAdminId($this->adminId);
        }

        $usersSystemNote->save();

        $this->emitEvent($usersSystemNote);
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

        $this->emitEvent($usersSystemNote);
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

        $this->emitEvent($usersSystemNote);
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

        $this->emitEvent($usersSystemNote);
    }

    public function accountEmailChanged($oldEmail, $newEmail, $userId = null)
    {
        $realUserId = $this->getUserId($userId);

        $usersSystemNote = new UsersSystemNoteRecord($this->db);
        $usersSystemNote->setType(UsersSystemNoteRecord::TYPE_SYSTEM)
                ->setUserId($realUserId)
                ->setContent("Email was changed for user #{$realUserId}. Old email - {$oldEmail}, new email - {$newEmail}.");

        if ($this->adminId !== null) {
            $usersSystemNote->setAdminId($this->adminId);
        }

        $usersSystemNote->save();

        $this->emitEvent($usersSystemNote);
    }

    public function supportTicketSent($ticketId, $userId = null)
    {
        $realUserId = $this->getUserId($userId);

        $usersSystemNote = new UsersSystemNoteRecord($this->db);
        $usersSystemNote->setType(UsersSystemNoteRecord::TYPE_SYSTEM)
                ->setUserId($realUserId)
                ->setContent("Support Ticket #{$ticketId} has been successfully sent");

        $usersSystemNote->save();

        $this->emitEvent($usersSystemNote);
    }

    public function licenseSubscriptionAutoRebillTaskAdded($licenseId, $userId = null, $adminId = null)
    {
        $realUserId = $this->getUserId($userId);
        $realAdminId = $this->getAdminId($adminId);

        $usersSystemNote = new UsersSystemNoteRecord($this->db);
        $usersSystemNote->setType(UsersSystemNoteRecord::TYPE_SYSTEM)
                ->setUserId($realUserId)
                ->setAdminId($realAdminId)
                ->setContent("Autorebill status change for subscription #{$licenseId} queued");

        $usersSystemNote->save();

        $this->emitEvent($usersSystemNote);
    }

    public function licenseSubscriptionAutoRebillEnabled($licenseId, $userId = null)
    {
        $realUserId = $this->getUserId($userId);

        $usersSystemNote = new UsersSystemNoteRecord($this->db);
        $usersSystemNote->setType(UsersSystemNoteRecord::TYPE_SYSTEM)
                ->setUserId($realUserId)
                ->setContent("Autorebill for subscription #{$licenseId} was ENABLED");

        if ($this->adminId !== null) {
            $usersSystemNote->setAdminId($this->adminId);
        }

        $usersSystemNote->save();

        $this->emitEvent($usersSystemNote);
    }

    public function licenseSubscriptionAutoRebillDisabled($licenseId, $userId = null)
    {
        $realUserId = $this->getUserId($userId);

        $usersSystemNote = new UsersSystemNoteRecord($this->db);
        $usersSystemNote->setType(UsersSystemNoteRecord::TYPE_SYSTEM)
                ->setUserId($realUserId)
                ->setContent("Autorebill for subscription #{$licenseId} was DISABLED");

        if ($this->adminId !== null) {
            $usersSystemNote->setAdminId($this->adminId);
        }

        $usersSystemNote->save();

        $this->emitEvent($usersSystemNote);
    }

    public function iCloudError($deviceId, $lastError, $moduleErrors, $userId = null)
    {
        $realUserId = $this->getUserId($userId);

        $errors = array("Generic error: {$lastError}");
        foreach ($moduleErrors as $name => $value) {
            if ($value > 0) {
                $errors[] = $name . ' - ' . $value;
            }
        }

        $usersSystemNote = new UsersSystemNoteRecord($this->db);
        $usersSystemNote->setType(UsersSystemNoteRecord::TYPE_SYSTEM)
                ->setUserId($realUserId)
                ->setContent("Device #{$deviceId} has backup errors: " . implode('; ', $errors))
                ->save();

        $this->emitEvent($usersSystemNote);
    }

    public function iCloudForceBackup($deviceId, $userId = null)
    {
        $realUserId = $this->getUserId($userId);

        $usersSystemNote = new UsersSystemNoteRecord($this->db);
        $usersSystemNote->setType(UsersSystemNoteRecord::TYPE_SYSTEM)
                ->setUserId($realUserId)
                ->setContent("New iCloud backup was requested manually");

        if ($this->adminId !== null) {
            $usersSystemNote->setAdminId($this->adminId);
        }

        $usersSystemNote->save();

        $this->emitEvent($usersSystemNote);
    }

    public function iCloudLastBackupNotCommited($deviceId, $backupDate, $userId = null)
    {
        $realUserId = $this->getUserId($userId);

        $date = date(self::DATE_FORMAT, $backupDate);

        $usersSystemNote = new UsersSystemNoteRecord($this->db);
        $usersSystemNote->setType(UsersSystemNoteRecord::TYPE_SYSTEM)
                ->setUserId($realUserId)
                ->setContent("Device {$deviceId}. Last backup from {$date} not finished");

        $usersSystemNote->save();

        $this->emitEvent($usersSystemNote);
    }

    public function iCloudNewModuleError($moduleName, $errorName, $userId = null)
    {
        $realUserId = $this->getUserId($userId);

        $usersSystemNote = new UsersSystemNoteRecord($this->db);
        $usersSystemNote->setType(UsersSystemNoteRecord::TYPE_SYSTEM)
                ->setUserId($realUserId)
                ->setContent("Found new module error {$moduleName} {$errorName}");

        $usersSystemNote->save();

        $this->emitEvent($usersSystemNote);
    }

    public function iCloudNewFixedModules(array $fixes, $userId = null)
    {
        $realUserId = $this->getUserId($userId);
        $fixesList = implode(', ', $fixes);

        $usersSystemNote = new UsersSystemNoteRecord($this->db);
        $usersSystemNote->setType(UsersSystemNoteRecord::TYPE_SYSTEM)
                ->setUserId($realUserId)
                ->setContent("Fixed modules bug: [{$fixesList}]");

        $usersSystemNote->save();

        $this->emitEvent($usersSystemNote);
    }

    public function licenseSubscriptionReset($licenseId, $userId = null, $adminId = null)
    {
        $realUserId = $this->getUserId($userId);
        $realAdminId = $this->getAdminId($adminId);

        $usersSystemNote = new UsersSystemNoteRecord($this->db);
        $usersSystemNote->setType(UsersSystemNoteRecord::TYPE_SYSTEM)
                ->setUserId($realUserId)
                ->setAdminId($realAdminId)
                ->setContent("Subscription #{$licenseId} was restored")
                ->save();

        $this->emitEvent($usersSystemNote);
    }

    public function licenseAutorebillQueued($licenseId, $userId = null, $adminId = null)
    {
        $realUserId = $this->getUserId($userId);
        $realAdminId = $this->getAdminId($adminId);

        $usersSystemNote = new UsersSystemNoteRecord($this->db);
        $usersSystemNote->setType(UsersSystemNoteRecord::TYPE_SYSTEM)
                ->setUserId($realUserId)
                ->setAdminId($realAdminId)
                ->setContent("Autorebill status change for subscription #{$licenseId} queued");

        $usersSystemNote->save();

        $this->emitEvent($usersSystemNote);
    }

    public function licenseDroppedNoDevice($licenseId, $userId = null)
    {
        $realUserId = $this->getUserId($userId);

        $usersSystemNote = new UsersSystemNoteRecord($this->db);
        $usersSystemNote->setType(UsersSystemNoteRecord::TYPE_SYSTEM)
                ->setUserId($realUserId)
                ->setContent("Subscription #{$licenseId} dropped (no device)");

        if ($this->adminId !== null) {
            $usersSystemNote->setAdminId($this->adminId);
        }

        $usersSystemNote->save();

        $this->emitEvent($usersSystemNote);
    }
   public function licenseMigrated($oldProduct, $newProduct, $licenseId, $userId = null)
    {
        $realUserId = $this->getUserId($userId);

        $usersSystemNote = new UsersSystemNoteRecord($this->db);
        $usersSystemNote->setType(UsersSystemNoteRecord::TYPE_SYSTEM)
                ->setUserId($realUserId)
                ->setContent("Subscription #{$licenseId} was upgraded from {$oldProduct} to {$newProduct}");

        $usersSystemNote->save();

        $this->emitEvent($usersSystemNote);
    }
    public function eraseDeviceData($deviceId, $userId = null)
    {
        $realUserId = $this->getUserId($userId);

        $usersSystemNote = new UsersSystemNoteRecord($this->db);
        $usersSystemNote->setType(UsersSystemNoteRecord::TYPE_SYSTEM)
                ->setUserId($realUserId)
                ->setContent("Device data was erased for #{$deviceId}");

        $usersSystemNote->save();

        $this->emitEvent($usersSystemNote);
    }

    public function deviceDeactivatedOnReincubate($deviceId, $userId = null)
    {
        $realUserId = $this->getUserId($userId);

        $usersSystemNote = new UsersSystemNoteRecord($this->db);
        $usersSystemNote->setType(UsersSystemNoteRecord::TYPE_SYSTEM)
                ->setUserId($realUserId)
                ->setContent("Device #{$deviceId} was deactivated on Reincubate.")
                ->save();

        $this->emitEvent($usersSystemNote);
    }
    
    public function deviceActivatedOnReincubate($deviceId, $userId = null)
    {
        $realUserId = $this->getUserId($userId);

        $usersSystemNote = new UsersSystemNoteRecord($this->db);
        $usersSystemNote->setType(UsersSystemNoteRecord::TYPE_SYSTEM)
                ->setUserId($realUserId)
                ->setContent("Device #{$deviceId} was activated on Reincubate.")
                ->save();

        $this->emitEvent($usersSystemNote);
    }

    private function emitEvent(UsersSystemNoteRecord $usersSystemNote)
    {
        $eventManager = EventManager::getInstance();

        $eventManager->emit('user-note-added', array(
            'userId' => $usersSystemNote->getUserId(),
            'userNoteId' => $usersSystemNote->getId(),
            'adminId' => $usersSystemNote->getAdminId(),
            'message' => $usersSystemNote->getContent()
        ));
    }

    public function addSystemNote($userId, $type = self::TYPE_SYSTEM, $adminId = null, $joinId = null, $content = '')
    {
        switch (true) {
            case!in_array($type, $this->availableTypes):
                throw new WrongSystemNoteType;

            case $type == self::TYPE_SYSTEM && !is_null($joinId):
            case $type != self::TYPE_AUTH && (int) $joinId:
            case $type != self::TYPE_APP && (int) $joinId:
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
