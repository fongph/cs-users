<?php

namespace CS\Users;

use EventManager\EventManager;

/**
 * Description of JiraLogger
 *
 * @author root
 */
class JiraLogger
{

    /**
     *
     * @var \PDO
     */
    private $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function registerListeners()
    {
        $manager = EventManager::getInstance();

        $manager->on('email-sended', function($data) {
            if (!$data['system']) {
                $this->logEmailSended($data);
            }
        });

        $manager->on('user-note-added', function($data) {
            $this->logUserNoteAdded($data);
        });

        $manager->on('user-deleted', function($data) {
            $this->logEvent($data, 'user-deleted');
        });

        $this->registerBillingListeners($manager);
        $this->registerFrontListeners($manager);
    }

    private function logEmailSended($data)
    {
        if (isset($data['userId'])) {
            $this->logEvent($data, 'email-sended');
        } else {
            $this->logEventWithEmail($data, 'email-sended');
        }
    }

    private function logUserNoteAdded($data)
    {
        $this->logEvent($data, 'user-note-added');
    }

    private function logEvent($data, $event)
    {
        if (!isset($data['userId'])) {
            return false;
        }

        $serializedData = $this->pdo->quote(json_encode($data));

        $userId = $this->pdo->quote($data['userId']);
        $eventName = $this->pdo->quote($event);

        $this->pdo->exec("INSERT INTO `jira_logs` SET `user_id` = {$userId}, `event` = {$eventName}, `data` = {$serializedData}");
    }

    private function logEventWithEmail($data, $event)
    {
        if (!isset($data['email'])) {
            return false;
        }

        $serializedData = $this->pdo->quote(json_encode($data));

        $email = $this->pdo->quote($data['email']);
        $eventName = $this->pdo->quote($event);

        $this->pdo->exec("INSERT INTO `jira_logs` SET `email` = {$email}, `event` = {$eventName}, `data` = {$serializedData}");
    }

    private function registerBillingListeners(EventManager $manager)
    {
        $manager->on('billing-sale', function($data) {
            $this->logEvent($data, 'billing-sale');
        });

        $manager->on('billing-rebill', function($data) {
            $this->logEvent($data, 'billing-rebill');
        });

        $manager->on('billing-refund', function($data) {
            $this->logEvent($data, 'billing-refund');
        });

        $manager->on('billing-fraud', function($data) {
            $this->logEvent($data, 'billing-fraud');
        });

        $manager->on('billing-rebill-failed', function($data) {
            $this->logEvent($data, 'billing-rebill-failed');
        });

        // Tickets creation

        $manager->on('billing-order-completed', function($data) {
            $this->logEvent($data, 'billing-order-completed');
        });

        $manager->on('billing-order-canceled', function($data) {
            if (isset($data['userId'])) {
                $this->logEvent($data, 'email-sended');
            } else {
                $this->logEventWithEmail($data, 'email-sended');
            }
        });
    }

    private function registerFrontListeners(EventManager $manager)
    {
        // Tickets creation
        $manager->on('front-order-pending', function($data) {
            $this->logEvent($data, 'front-order-pending');
        });

        $manager->on('front-registration-completed', function($data) {
            $this->logEventWithEmail($data, 'front-registration-completed');
        });

        $manager->on('front-subscription-completed', function($data) {
            $this->logEvent($data, 'front-subscription-completed');
        });

        $manager->on('front-contact-us-completed', function($data) {
            $this->logEventWithEmail($data, 'front-subscription-completed');
        });

        $manager->on('front-compatibility-completed', function($data) {
            $this->logEventWithEmail($data, 'front-subscription-completed');
        });

        $manager->on('front-carriers-completed', function($data) {
            $this->logEventWithEmail($data, 'front-subscription-completed');
        });

        $manager->on('front-registration-trial-completed', function($data) {
            $this->logEvent($data, 'front-registration-trial-completed');
        });
    }

}
