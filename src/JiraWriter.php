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
    const CUSTOM_FIELD_DEVICES_VALUE_NO_DEVICES = 10021;
    const CUSTOM_FIELD_DEVICES_VALUE_ADDED_DEVICE = 10022;
    const CUSTOM_FIELD_DEVICES_VALUE_DELETED_DEVICE = 10023;
    const CUSTOM_FIELD_SUBSCRIPTION = 10021;
    const CUSTOM_FIELD_PROFILE_URL = 10025;
    const CUSTOM_FIELD_ERRORS = 10026;
    const CUSTOM_FIELD_DEVICE_MODEL = 10022;
    const CUSTOM_FIELD_OS = 10023;
    const CUSTOM_FIELD_OS_VERSION = 10024;
    const CUSTOM_FIELD_MANUAL_SOURCE = 10027;
    const CUSTOM_FIELD_PROBLEMS = 10014;

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
                        ->customField(self::CUSTOM_FIELD_PROFILE_URL, $this->getUserProfileUrl($userId));
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
                ->customField(self::CUSTOM_FIELD_PROFILE_URL, $this->getUserProfileUrl($userId));

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
        return $this->pdo->query("SELECT *, UNIX_TIMESTAMP(`created_at`) as time FROM `jira_logs` WHERE `processed` = 0")->fetchAll(\PDO::FETCH_ASSOC);
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

        return 'unknown';
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

        return 'unknown';
    }

    public function updateDueDate($userId, $userEmail, Issue $issue)
    {
        $timestamp = $this->getUserDueDateTimestamp($userId);

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

}
