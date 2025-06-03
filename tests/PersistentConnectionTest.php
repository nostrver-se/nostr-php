<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use swentel\nostr\Message\RequestMessage;
use swentel\nostr\Relay\Relay;
use swentel\nostr\Request\PersistentConnection;
use swentel\nostr\RelayResponse\RelayResponse;
use swentel\nostr\Subscription\Subscription;
use swentel\nostr\Filter\Filter;

class PersistentConnectionTest extends TestCase
{
    private const TEST_RELAY_URL = 'wss://relay.damus.io';
    private Relay $relay;
    private PersistentConnection $connection;
    private int $startTime;
    private int $timeoutSeconds = 3;

    protected function setUp(): void
    {
        parent::setUp();

        $this->startTime = time();

        // Set up basic test components
        $this->relay = new Relay(self::TEST_RELAY_URL);

        $subscription = new Subscription();
        $filter = new Filter();
        $filter->setKinds([1]);
        $filter->setLimit(1);

        $requestMessage = new RequestMessage($subscription->getId(), [$filter]);
        $this->connection = new PersistentConnection($this->relay, $requestMessage);
    }

    public function testMessageCallbacksAreInvoked(): void
    {
        $callbackInvoked = false;

        // Set up a test callback
        $this->connection->onReceive(function ($response) use (&$callbackInvoked) {
            $callbackInvoked = true;
            $this->assertInstanceOf(RelayResponse::class, $response);
            $this->assertTrue($this->relay->getClient()->isConnected());
            $this->assertTrue($this->relay->getClient()->isRunning());
            // Disconnect and close after 3 seconds
            if (time() - $this->startTime >= $this->timeoutSeconds) {
                $this->connection->close();
            }
        });

        $this->connection->transmit();

        $this->assertTrue($callbackInvoked, 'Callback should have been invoked');
    }

    public function testClearCallbacks(): void
    {
        $callbackInvoked = false;

        $this->connection->onReceive(function ($response) use (&$callbackInvoked) {
            $callbackInvoked = true;
            $this->assertFalse($this->relay->getClient()->isConnected());
            $this->assertFalse($this->relay->getClient()->isRunning());
            $this->assertInstanceOf(RelayResponse::class, $response);
            // Disconnect and close after 3 seconds
            if (time() - $this->startTime >= $this->timeoutSeconds) {
                $this->connection->close();
            }
        });

        $this->connection->clearCallbacks();

        $this->assertFalse($callbackInvoked, 'Callback should not have been invoked after clearing');
    }

    public function testPrintMessagesFlag(): void
    {
        ob_start();
        $this->connection->onReceive(function ($response) {
            // Disconnect and close after 3 seconds
            print $this->relay->getClient()->isConnected();
            if (time() - $this->startTime >= $this->timeoutSeconds) {
                $this->connection->close();
            }
        });

        $this->connection->setPrintMessages(true);
        $this->connection->transmit();

        $output = ob_get_clean();
        $this->assertIsString($output);
        $this->assertNotEmpty($output);
    }
}
