<?php

namespace CS\Users;

use EventManager\EventManager;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use Swarrot\Broker\Message;
use Swarrot\Broker\MessageProvider\PeclPackageMessageProvider;
use Swarrot\Broker\MessagePublisher\PhpAmqpLibMessagePublisher;
use PhpAmqpLib\Connection\AMQPConnection;
use PhpAmqpLib\Channel\AMQPChannel;




/**
 * Description of JiraLogger
 *
 * @author root
 */
class JiraLogger
{
    const QUEUE_NAME = 'jira';

    /**
     *
     * @var \PDO
     */
    private $pdo;


    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function setPdo(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function setQueue()
    {
        $queueConfig = \CS\Settings\GlobalSettings::getQueueConfig();
        // Create connection
        $connection = new AMQPStreamConnection($queueConfig['host'], $queueConfig['port'], $queueConfig['user'], $queueConfig['password']);
        $queueChannel = new AMQPChannel($connection);
        $connection->channel();

        return $queueChannel;
    }

    public function registerListeners()
    {
        $manager = EventManager::getInstance();

//        $manager->on('email-sended', function($data) {
//            if (!$data['system']) {
//                $this->logEmailSended($data);
//            }
//        });

        $manager->on('user-note-added', function($data) {
            $this->logUserNoteAdded($data);
        });

        $manager->on('device-added', function($data) {
            $this->logEvent($data, 'device-added');
        });

        $manager->on('device-deleted', function($data) {
            $this->logEvent($data, 'device-deleted');
        });
        
        $manager->on('device-icloud-backup-processed', function($data) {
            $this->logEvent($data, 'device-icloud-backup-processed');
        });

        $manager->on('icloud-two-step-verification-error', function($data) {
            $this->logEvent($data, 'icloud-two-step-verification-error');
        });

        $manager->on('user-deleted', function($data) {
            $this->logEventWithEmail($data, 'user-deleted');
        });
        
        $manager->on('user-custom-password-saved', function($data) {
            $this->logEvent($data, 'user-custom-password-saved');
        });

        $this->registerBillingListeners($manager);
        $this->registerFrontListeners($manager);
        $this->registerCpListeners($manager);
        $this->registerApiListeners($manager);
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

        $queueChannel = $this->setQueue();

        $publisher = new PhpAmqpLibMessagePublisher($queueChannel, '');

        $message = new Message(json_encode(array_merge(array('event' => $event), $data)));
        $publisher->publish($message, self::QUEUE_NAME);
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

        $queueChannel = $this->setQueue();
        $publisher = new PhpAmqpLibMessagePublisher($queueChannel, '');

        $message = new Message(json_encode(array_merge(array('event' => $event), $data)));
        $publisher->publish($message, self::QUEUE_NAME);
    }

    private function registerCpListeners(EventManager $manager)
    {
        $manager->on('cp-support-completed', function ($data) {
            $this->logEvent($data, 'cp-support-completed');
        });
        
        $manager->on('cp-license-cancelation-completed', function ($data) {
            $this->logEvent($data, 'cp-license-cancelation-completed');
        });
        
        $manager->on('cp-lost-password-completed', function ($data) {
            $this->logEventWithEmail($data, 'cp-lost-password-completed');
        });
    }
    
    private function registerApiListeners(EventManager $manager)
    {
        $manager->on('device-application-deleted', function ($data) {
            $this->logEvent($data, 'device-application-deleted');
        });
        
        $manager->on('device-admin-rights-removed', function ($data) {
            $this->logEvent($data, 'device-admin-rights-removed');
        });
    }

    private function registerBillingListeners(EventManager $manager)
    {
        $manager->on('billing-sale', function ($data) {
            $this->logEvent($data, 'billing-sale');
        });

        $manager->on('billing-rebill', function ($data) {
            $this->logEvent($data, 'billing-rebill');
        });

        $manager->on('billing-refund', function ($data) {
            $this->logEvent($data, 'billing-refund');
        });

        $manager->on('billing-fraud', function ($data) {
            $this->logEvent($data, 'billing-fraud');
        });

        $manager->on('billing-rebill-failed', function ($data) {
            $this->logEvent($data, 'billing-rebill-failed');
        });

        $manager->on('billing-license-added', function ($data) {
            $this->logEvent($data, 'billing-license-added');
        });

        $manager->on('billing-order-started', function ($data) {
            if (isset($data['userId']) && $data['userId'] > 0) {
                $this->logEvent($data, 'billing-order-started');
            } else {
                $this->logEventWithEmail($data, 'billing-order-started');
            }
        });

        $manager->on('billing-order-completed', function ($data) {
            $this->logEvent($data, 'billing-order-completed');
        });

        $manager->on('billing-order-canceled', function ($data) {
            if (isset($data['userId'])) {
                $this->logEvent($data, 'billing-order-canceled');
            } else {
                $this->logEventWithEmail($data, 'billing-order-canceled');
            }
        });
        
        $manager->on('billing-autorebill-enabled', function ($data) {
            $this->logEvent($data, 'billing-autorebill-enabled');
        });
        
        $manager->on('billing-autorebill-disabled', function ($data) {
            $this->logEvent($data, 'billing-autorebill-disabled');
        });

        $manager->on('billing-order-ga-source', function ($data) {
            $this->logEventWithEmail($data, 'billing-order-ga-source');
        });
        
        $manager->on('license-added', function ($data) {
            $this->logEvent($data, 'license-added');
        });
        
        $manager->on('license-dropped', function ($data) {
            $this->logEvent($data, 'license-dropped');
        });
        
        $manager->on('license-assigned', function ($data) {
            $this->logEvent($data, 'license-assigned');
        });
        
        $manager->on('license-unassigned', function ($data) {
            $this->logEvent($data, 'license-unassigned');
        });
        
        $manager->on('license-restored', function ($data) {
            $this->logEvent($data, 'license-restored');
        });
        
        $manager->on('license-expired', function ($data) {
            $this->logEvent($data, 'license-expired');
        });
        
        $manager->on('license-updated', function ($data) {
            $this->logEvent($data, 'license-updated');
        });

        $manager->on('license-migrated', function ($data) {
            $this->logEvent($data, 'license-migrated');
        });
    }

    private function registerFrontListeners(EventManager $manager)
    {
        // Tickets creation
        $manager->on('front-order-pending', function ($data) {
            $this->logEvent($data, 'front-order-pending');
        });

        $manager->on('front-registration-completed', function ($data) {
            $this->logEventWithEmail($data, 'front-registration-completed');
        });

        $manager->on('front-subscription-completed', function ($data) {
            $this->logEventWithEmail($data, 'front-subscription-completed');
        });

        $manager->on('front-contact-us-completed', function ($data) {
            $this->logEventWithEmail($data, 'front-contact-us-completed');
        });

        $manager->on('front-compatibility-completed', function ($data) {
            $this->logEventWithEmail($data, 'front-compatibility-completed');
        });

        $manager->on('front-carriers-completed', function ($data) {
            $this->logEventWithEmail($data, 'front-carriers-completed');
        });

        $manager->on('front-registration-trial-completed', function ($data) {
            $this->logEvent($data, 'front-registration-trial-completed');
        });
        
        $manager->on('front-livechat', function ($data) {
            $this->logEventWithEmail($data, 'front-livechat');
        });

        $manager->on('front-employee-request-completed', function ($data) {
            $this->logEventWithEmail($data, 'front-employee-request-completed');
        });
    }

}
