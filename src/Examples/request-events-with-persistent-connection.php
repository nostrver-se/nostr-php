<?php

declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';

use swentel\nostr\Relay\Relay;
use swentel\nostr\Subscription\Subscription;
use swentel\nostr\Filter\Filter;
use swentel\nostr\Message\RequestMessage;
use swentel\nostr\Request\PersistentConnection;

try {
    // Setup relay and subscription
    $relay = new Relay('wss://relay.nostr.band');
    $subscription = new Subscription();
    $subscriptionId = $subscription->getId();

    // Create filter for text notes
    $filter = new Filter();
    $filter->setKinds([1]); // kind 1 = text note
    $filter->setLimit(25);

    // Create request message
    $requestMessage = new RequestMessage($subscriptionId, [$filter]);

    // Create persistent connection.
    $connection = new PersistentConnection($relay, $requestMessage);

    $startTime = time();
    $timeoutSeconds = 5;

    // Set callback to handle received events.
    $connection->onReceive(function ($response) use ($startTime, $timeoutSeconds, $connection) {
        if (isset($response->event->content)) {
            $content = $response->event->content;
            $timestamp = date('Y-m-d H:i:s', $response->event->created_at);
            // Output received content from event.
            print "[$timestamp] New event (note) received:" . PHP_EOL;
            print $content . PHP_EOL;

            // Timeout limit reached, so we close the connection and exit the script here.
            if (time() - $startTime >= $timeoutSeconds) {
                print PHP_EOL;
                print "Reached timeout of {$timeoutSeconds} seconds, closing connection..." . PHP_EOL;
                $connection->close();
                exit(0);
            }
        }
    });

    // Disable automatic printing since we're handling it in callbacks.
    // Could be handy when debugging.
    $connection->setPrintMessages(false);

    // Now start transmitting and new received events will be printed.
    print "Starting to listen for messages..." . PHP_EOL;
    $connection->transmit();

} catch (Exception $e) {
    print 'Exception error: ' . $e->getMessage() . PHP_EOL;
}
