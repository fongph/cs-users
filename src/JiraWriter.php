<?php

namespace CS\Users;

use JiraClient\JiraClient,
    JiraClient\Resource\Issue,
    JiraClient\Resource\Field;

/**
 * Description of JiraWriter
 *
 * @author root
 */
class JiraWriter
{

    const PROJECT_KEY = 'LEAD';
    const ISSUE_TYPE_POTENTIAL = 'Potential Client';
    const ISSUE_TYPE_ACTIVE = 'Active Client';
    const CUSTOM_FIELD_EMAIL = 10015;
    const CUSTOM_FIELD_FIRST_NAME = 10016;
    const CUSTOM_FIELD_LAST_NAME = 10017;
    const CUSTOM_FIELD_SOURCE = 10018;
    const CUSTOM_FIELD_DEVICES = 10020;
    const CUSTOM_FIELD_DEVICES_VALUE_NO_DEVICES = 'No Devices';
    const CUSTOM_FIELD_DEVICES_VALUE_ADDED_DEVICE = 'Added Device';
    const CUSTOM_FIELD_DEVICES_VALUE_DELETED_DEVICE = 'Deleted Device';
    const CUSTOM_FIELD_SUBSCRIPTION = 10021;
    const CUSTOM_FIELD_PROFILE_URL = 10025;
    const CUSTOM_FIELD_ERRORS = 10026;
    const CUSTOM_FIELD_DEVICE_MODEL = 10022;
    const CUSTOM_FIELD_OS = 10023;
    const CUSTOM_FIELD_OS_VERSION = 10024;
    const CUSTOM_FIELD_MANUAL_SOURCE = 10027;
    const CUSTOM_FIELD_PROBLEMS = 10014;
    const CUSTOM_FIELD_PROBLEMS_AVAILABLE_SUBSCRIPTIONS = 'Available Subscription';
    const CUSTOM_FIELD_PROBLEMS_DELETED_DEVICE = 'Deleted device';
    const CUSTOM_FIELD_PROBLEMS_CANCELED_AUTOREBILL = 'Canceled Autorebill';
    const CUSTOM_FIELD_PROBLEMS_ICLOUD_PROBLEM = 'iCloud Problem';
    const CUSTOM_FIELD_PROBLEMS_OLD_LAST_LOGIN = 'Old Last Login';
    const CUSTOM_FIELD_PROBLEMS_OLD_LAST_SYNCHRONIZATION = 'Old Last Synch';
    const CUSTOM_FIELD_PROBLEMS_APPLICATION_DELETED = 'App Deleted';
    const CUSTOM_FIELD_PROBLEMS_ADMIN_RIGHTS_REMOVED = 'Admin Rights Removed';
    const CUSTOM_FIELD_USER_JOURNEY = 10032;
    const CUSTOM_FIELD_USER_JOURNEY_PRE_SALE = 'Pre-Sale Request';
    const CUSTOM_FIELD_USER_JOURNEY_TRIAL = 'Trial';
    const CUSTOM_FIELD_USER_JOURNEY_SALE = 'Sale';
    const TRANSITION_ACTIVE_TO_NEW = 231;
    const TRANSITION_ACTIVE_TO_EXPIRED = 251;
    const TRANSITION_ACTIVE_TO_REFUND = 241;
    const TRANSITION_POTENTIAL_TO_NEW = 101;

    private $sourceValues = array(
        'billing-order-completed' => 10012,
        'front-registration-trial-completed' => 10013,
        'billing-order-canceled' => 10014,
        'front-registration-completed' => 10015,
        'front-subscription-completed' => 10016,
        'front-contact-us-completed' => 10017,
        'front-compatibility-completed' => 10018,
        'front-carriers-completed' => 10019,
        'front-order-pending' => 10036
    );
    private $sourceNames = array(
        'billing-order-completed' => 'Order Completed',
        'front-registration-trial-completed' => 'Trial Registration',
        'billing-order-canceled' => 'Order Canceled Purchase',
        'front-registration-completed' => 'Regular Registration',
        'front-subscription-completed' => 'Subscribed Email',
        'front-contact-us-completed' => 'Contact Us (Pumpic)',
        'front-compatibility-completed' => 'Compatibility',
        'front-carriers-completed' => 'Carriers Landing',
        'front-order-pending' => 'Order Pending'
    );

    /**
     *
     * @var \JiraClient\JiraClient
     */
    private $client;

    /**
     *
     * @var \PDO
     */
    private $pdo;

    public function __construct(JiraClient $client, \PDO $pdo)
    {
        $this->client = $client;
        $this->pdo = $pdo;
    }

    public function createNewActiveIssue($email, $userId)
    {
        return $this->client->issue()
                        ->create(self::PROJECT_KEY, self::ISSUE_TYPE_ACTIVE)
                        ->field(Field::SUMMARY, $email)
                        ->customField(self::CUSTOM_FIELD_EMAIL, $email)
                        ->customField(self::CUSTOM_FIELD_PROFILE_URL, $this->getUserProfileUrl($userId))
                        ->customField(self::CUSTOM_FIELD_DEVICES, $this->getDevicesFieldValue($userId));
    }

    public function createNewPotentialIssue($email)
    {
        return $this->client->issue()
                        ->create(self::PROJECT_KEY, self::ISSUE_TYPE_POTENTIAL)
                        ->field(Field::SUMMARY, $email)
                        ->customField(self::CUSTOM_FIELD_EMAIL, $email);
    }

    public function createActiveClientIssue(Issue $oldIssue, $userId)
    {
        $newIssue = $this->client->issue()->create(self::PROJECT_KEY, self::ISSUE_TYPE_ACTIVE);

        $newIssue->field(Field::SUMMARY, $oldIssue->getSummary())
                ->field(Field::DESCRIPTION, $oldIssue->getDescription())
                ->customField(self::CUSTOM_FIELD_EMAIL, $oldIssue->getCustomField(self::CUSTOM_FIELD_EMAIL))
                ->customField(self::CUSTOM_FIELD_FIRST_NAME, $oldIssue->getCustomField(self::CUSTOM_FIELD_FIRST_NAME))
                ->customField(self::CUSTOM_FIELD_LAST_NAME, $oldIssue->getCustomField(self::CUSTOM_FIELD_LAST_NAME))
                ->customField(self::CUSTOM_FIELD_PROFILE_URL, $this->getUserProfileUrl($userId))
                ->customField(self::CUSTOM_FIELD_DEVICES, $this->getDevicesFieldValue($userId))
                ->customFieldAdd(self::CUSTOM_FIELD_USER_JOURNEY, self::CUSTOM_FIELD_USER_JOURNEY_PRE_SALE);

        if ($oldIssue->getCustomField(self::CUSTOM_FIELD_SOURCE) !== null) {
            $source = $oldIssue->getCustomField(self::CUSTOM_FIELD_SOURCE);
            $newIssue->customField(self::CUSTOM_FIELD_SOURCE, $source['value']);
        }

        if ($oldIssue->getCustomField(self::CUSTOM_FIELD_MANUAL_SOURCE) !== null) {
            $manualSource = $oldIssue->getCustomField(self::CUSTOM_FIELD_MANUAL_SOURCE);
            $newIssue->customField(self::CUSTOM_FIELD_MANUAL_SOURCE, $manualSource['value']);
        }

        $createdIssue = $newIssue->execute();

        $comments = $oldIssue->getComments()->getList();

        foreach ($comments as $comment) {
            $createdIssue->addComment($comment->getBody());
        }

        $this->client->issue()->delete($oldIssue->getId());

        return $createdIssue;
    }

    private function getUserProfileUrl($userId)
    {
        return 'http://control-admin.pumpic.com/customer/' . $userId;
    }

    /**
     * 
     * @param type $email
     * @return \JiraClient\Resource\Issue | boolean
     */
    public function getIssueByUserEmail($email)
    {

        $jql = "project = " . self::PROJECT_KEY . " AND Email ~ '{$email}'";

        $result = $this->client->issue()->search($jql, '*all', null, 1);

        if ($result->getTotal() == 0) {
            return false;
        }

        $list = $result->getList();

        return $list[0];
    }

    public function getUserEmail($id)
    {
        $escapedId = $this->pdo->quote($id);

        return $this->pdo->query("SELECT `login` FROM `users` WHERE `id` = {$escapedId} LIMIT 1")->fetchColumn();
    }

    public function getNotProcessedRecords()
    {
        return $this->pdo->query("SELECT *, UNIX_TIMESTAMP(`created_at`) as time FROM `jira_logs` WHERE `processed` = 0 ORDER BY `event`")->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function setLogRecordProcessed($id)
    {
        $escapedId = $this->pdo->quote($id);
        $this->pdo->exec("UPDATE `jira_logs` SET `processed` = 1, `updated_at` = NOW() WHERE `id` = {$escapedId}");
    }

    public function getUserDueDateByEmail($email)
    {
        $escapedEmail = $this->pdo->quote($email);
        return $this->pdo->query("SELECT 
                                MIN(l.`expiration_date`) 
                            FROM `licenses` l
                            INNER JOIN `users` u ON l.`user_id` = u.`id`
                            WHERE
                                (l.`status` = 'active' OR l.`status` = 'available') AND
                                u.`login` = {$escapedEmail}")->fetchColumn();
    }

    private function getUserDueDateTimestamp($userId)
    {
        $escapedUserId = $this->pdo->quote($userId);
        $minTime = $this->pdo->query("SELECT 
                                MIN(l.`expiration_date`) 
                            FROM `licenses` l
                            WHERE
                                (l.`status` = 'active' OR l.`status` = 'available') AND
                                l.`user_id` = {$escapedUserId}")->fetchColumn();

        if ($minTime > 0) {
            return $minTime;
        }

        return 0;
    }

    public function getDevicesFieldValue($userId)
    {
        $hasActive = $this->hasActiveDevices($userId);
        $hasDeleted = $this->hasDeletedDevices($userId);

        if (!$hasActive && !$hasDeleted) {
            return array(self::CUSTOM_FIELD_DEVICES_VALUE_NO_DEVICES);
        }

        $result = array();

        if ($hasActive) {
            $result[] = self::CUSTOM_FIELD_DEVICES_VALUE_ADDED_DEVICE;
        }

        if ($hasDeleted) {
            $result[] = self::CUSTOM_FIELD_DEVICES_VALUE_DELETED_DEVICE;
        }

        return $result;
    }

    private function hasActiveDevices($userId)
    {
        $escapedUserId = $this->pdo->quote($userId);
        return $this->pdo->query("SELECT id FROM `devices` WHERE `user_id` = {$escapedUserId} AND `deleted` = 0 LIMIT 1")->fetchColumn() !== false;
    }

    private function hasDeletedDevices($userId)
    {
        $escapedUserId = $this->pdo->quote($userId);
        return $this->pdo->query("SELECT id FROM `devices` WHERE `user_id` = {$escapedUserId} AND `deleted` = 1 LIMIT 1")->fetchColumn() !== false;
    }

    private function hasAvailableLicenses($userId)
    {
        $escapedUserId = $this->pdo->quote($userId);
        $status = $this->pdo->quote(\CS\Models\License\LicenseRecord::STATUS_AVAILABLE);
        return $this->pdo->query("SELECT `id` FROM `licenses` WHERE `user_id` = {$escapedUserId} AND `status` = {$status} LIMIT 1")->fetchColumn() !== false;
    }
    
    public function hasAliveLicenses($userId)
    {
        $escapedUserId = $this->pdo->quote($userId);
        $statusAvailable = $this->pdo->quote(\CS\Models\License\LicenseRecord::STATUS_AVAILABLE);
        $statusActive = $this->pdo->quote(\CS\Models\License\LicenseRecord::STATUS_ACTIVE);
        return $this->pdo->query("SELECT `id` FROM `licenses` WHERE `user_id` = {$escapedUserId} AND (`status` = {$statusAvailable} OR `status` = {$statusActive}) LIMIT 1")->fetchColumn() !== false;
    }

    private function hasCanceledLicenseSubscriptionsAutorebill($userId)
    {
        $escapedUserId = $this->pdo->quote($userId);

        $statusAvailable = $this->pdo->quote(\CS\Models\License\LicenseRecord::STATUS_AVAILABLE);
        $statusActive = $this->pdo->quote(\CS\Models\License\LicenseRecord::STATUS_ACTIVE);
        $paymentMethodFastSpring = $this->pdo->quote(\CS\Models\Order\OrderRecord::PAYMENT_METHOD_FASTSPRING);

        return $this->pdo->query("SELECT 
                                        l.`id` 
                                    FROM `licenses` l
                                    INNER JOIN `subscriptions` s ON s.`license_id` = l.`id`
                                    WHERE 
                                        l.`user_id` = {$escapedUserId} AND
                                        l.`status` IN ({$statusAvailable}, {$statusActive}) AND
                                        s.`payment_method` = {$paymentMethodFastSpring} AND
                                        s.`auto` = 0
                                    LIMIT 1")->fetchColumn() !== false;
    }

    public function hasDevicesWithDeletedApplication($userId)
    {
        $escapedUserId = $this->pdo->quote($userId);
        return $this->pdo->query("SELECT `id` FROM `devices` WHERE `user_id` = {$escapedUserId} AND `application_deleted` = 1 AND `deleted` = 0 LIMIT 1")->fetchColumn() !== false;
    }

    public function hasDevicesWithAdminRightsDeleted($userId)
    {
        $escapedUserId = $this->pdo->quote($userId);
        return $this->pdo->query("SELECT `id` FROM `devices` WHERE `user_id` = {$escapedUserId} AND `admin_rights_deleted` = 1 AND `deleted` = 0 LIMIT 1")->fetchColumn() !== false;
    }

    public function hasDevicesWithiCloudError($userId)
    {
        $escapedUserId = $this->pdo->quote($userId);

        return $this->pdo->query("SELECT
                                        d.`id`
                                    FROM `devices` d
                                    INNER JOIN `devices_icloud` di ON di.`dev_id` = d.`id`
                                    WHERE
                                        d.`user_id` = {$escapedUserId} AND
                                        d.`deleted` = 0 AND
                                        di.`last_error` > 0
                                    LIMIT 1")->fetchColumn() !== false;
    }

    public function updateiCloudProblem($userId, Issue $issue)
    {
        if ($this->hasDevicesWithiCloudError($userId)) {
            $issue->update()
                    ->customFieldAdd(JiraWriter::CUSTOM_FIELD_PROBLEMS, JiraWriter::CUSTOM_FIELD_PROBLEMS_ICLOUD_PROBLEM)
                    ->execute();
            
            $this->setStatusNew($issue);
        } else {
            $issue->update()
                    ->customFieldRemove(JiraWriter::CUSTOM_FIELD_PROBLEMS, JiraWriter::CUSTOM_FIELD_PROBLEMS_ICLOUD_PROBLEM)
                    ->execute();
        }
    }

    public function updateCanceledAutorebillProblem($userId, Issue $issue)
    {
        if ($this->hasCanceledLicenseSubscriptionsAutorebill($userId)) {
            $issue->update()
                    ->customFieldAdd(JiraWriter::CUSTOM_FIELD_PROBLEMS, JiraWriter::CUSTOM_FIELD_PROBLEMS_CANCELED_AUTOREBILL)
                    ->execute();
            
            $this->setStatusNew($issue);
        } else {
            $issue->update()
                    ->customFieldRemove(JiraWriter::CUSTOM_FIELD_PROBLEMS, JiraWriter::CUSTOM_FIELD_PROBLEMS_CANCELED_AUTOREBILL)
                    ->execute();
        }
    }

    public function updateAvailableLicensesProblem($userId, Issue $issue)
    {
        if ($this->hasAvailableLicenses($userId)) {
            $issue->update()
                    ->customFieldAdd(JiraWriter::CUSTOM_FIELD_PROBLEMS, JiraWriter::CUSTOM_FIELD_PROBLEMS_AVAILABLE_SUBSCRIPTIONS)
                    ->execute();
            
            $this->setStatusNew($issue);
        } else {
            $issue->update()
                    ->customFieldRemove(JiraWriter::CUSTOM_FIELD_PROBLEMS, JiraWriter::CUSTOM_FIELD_PROBLEMS_AVAILABLE_SUBSCRIPTIONS)
                    ->execute();
        }
    }

    public function updateApplicationDeletedProblem($userId, Issue $issue)
    {
        if ($this->hasDevicesWithDeletedApplication($userId)) {
            $issue->update()
                    ->customFieldAdd(JiraWriter::CUSTOM_FIELD_PROBLEMS, JiraWriter::CUSTOM_FIELD_PROBLEMS_APPLICATION_DELETED)
                    ->execute();
            
            $this->setStatusNew($issue);
        } else {
            $issue->update()
                    ->customFieldRemove(JiraWriter::CUSTOM_FIELD_PROBLEMS, JiraWriter::CUSTOM_FIELD_PROBLEMS_APPLICATION_DELETED)
                    ->execute();
        }
    }

    public function updateAdminRightsRemovedProblem($userId, Issue $issue)
    {
        if ($this->hasDevicesWithAdminRightsDeleted($userId)) {
            $issue->update()
                    ->customFieldAdd(JiraWriter::CUSTOM_FIELD_PROBLEMS, JiraWriter::CUSTOM_FIELD_PROBLEMS_ADMIN_RIGHTS_REMOVED)
                    ->execute();
            
            $this->setStatusNew($issue);
        } else {
            $issue->update()
                    ->customFieldRemove(JiraWriter::CUSTOM_FIELD_PROBLEMS, JiraWriter::CUSTOM_FIELD_PROBLEMS_ADMIN_RIGHTS_REMOVED)
                    ->execute();
        }
    }

    public function moneyForamt($value)
    {
        if ($value < 0) {
            return '-$' . abs($value);
        }

        return '$' . $value;
    }

    public function getEventSourceValue($event)
    {
        return $this->sourceValues[$event];
    }

    public function getEventSourceName($event)
    {
        return $this->sourceNames[$event];
    }

    public function getOSName(\CS\Models\Device\DeviceRecord $device)
    {
        if ($device->getOS() == 'icloud') {
            return 'iOS-icloud';
        }

        if ($device->getOS() == 'ios') {
            return 'iOS-jailbreak';
        }

        if ($device->getOS() == 'android') {
            if ($device->getRootAccess()) {
                return 'Android-SU';
            } elseif ($device->getRooted()) {
                return 'Android-Rooted';
            }

            return 'Android';
        }

        return false;
    }

    public function buildLabelText($value)
    {
        return str_replace(' ', '-', $value);
    }

    public function getOSVersion(\CS\Models\Device\DeviceRecord $device)
    {
        if ($device->getOS() == 'icloud' || $device->getOS() == 'ios') {
            return $this->buildLabelText('iOS ' . $device->getOSVersion());
        }

        if ($device->getOS() == 'android') {
            $version = array_pop(explode('_', $device->getOSVersion()));

            return $this->buildLabelText('Android ' . $version);
        }

        return false;
    }

    public function updateDueDate($userId, $userEmail, Issue $issue, $default = 0)
    {
        $timestamp = $this->getUserDueDateTimestamp($userId);

        if ($timestamp == 0) {
            $timestamp = $default;
        }
        
        if ($timestamp > 0) {
            $summary = $userEmail . ' - ' . date('d.m.Y', $timestamp);

            $issue->update()
                    ->field(Field::SUMMARY, $summary)
                    ->field(Field::DUE_DATE, date_create('@' . $timestamp))
                    ->execute();
        } else {
            $issue->update()->field(Field::SUMMARY, $userEmail)
                    ->field(Field::DUE_DATE, null)
                    ->execute();
        }
    }

    public function setStatusNew(Issue $issue)
    {
        if ($issue->getIssueType()->getName() === JiraWriter::ISSUE_TYPE_ACTIVE) {
            $transitionId = self::TRANSITION_ACTIVE_TO_NEW;
        } elseif ($issue->getIssueType()->getName() === JiraWriter::ISSUE_TYPE_POTENTIAL) {
            $transitionId = self::TRANSITION_POTENTIAL_TO_NEW;
        } else {
            throw new Exception("Not supported issue type");
        }

        $issue->transition()->execute($transitionId);
    }

    public function setStatusRefund(Issue $issue)
    {
        if ($issue->getIssueType()->getName() !== JiraWriter::ISSUE_TYPE_ACTIVE) {
            throw new Exception("Not supported issue type");
        }

        $issue->transition()->execute(self::TRANSITION_ACTIVE_TO_REFUND);
    }

    public function setStatusExpired(Issue $issue)
    {
        if ($issue->getIssueType()->getName() !== JiraWriter::ISSUE_TYPE_ACTIVE) {
            throw new Exception("Not supported issue type");
        }

        $issue->transition()->execute(self::TRANSITION_ACTIVE_TO_EXPIRED);
    }

}
