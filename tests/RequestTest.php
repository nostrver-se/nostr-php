<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use swentel\nostr\Event\Event;
use swentel\nostr\Relay\Relay;
use swentel\nostr\RelayResponse\RelayResponse;
use swentel\nostr\RelayResponse\RelayResponseEose;
use swentel\nostr\RelayResponse\RelayResponseEvent;
use swentel\nostr\Subscription\Subscription;
use swentel\nostr\Filter\Filter;
use swentel\nostr\Message\RequestMessage;
use swentel\nostr\Request\Request;

class RequestTest extends TestCase
{
    private const RELAY_URL = 'wss://relay.damus.io';
    private const TEST_PUBKEY = '884704bd421721e292edbec8466287dd3a3c834c2ed269822b95a85e4f8a0c47';

    protected Relay $relay;
    protected Subscription $subscription;

    protected function setUp(): void
    {
        parent::setUp();
        $this->relay = new Relay(self::RELAY_URL);
        $this->subscription = new Subscription();
        $this->subscription->setId();
    }

    /**
     * Creates a filter for testing with specified kinds and limit.
     */
    private function createFilter(array $kinds, int $limit = 3): Filter
    {
        $filter = new Filter();
        $filter->setKinds($kinds);
        $filter->setLimit($limit);
        return $filter;
    }

    /**
     * Creates a request message and sends it to the relay.
     */
    private function sendRequest(Filter $filter): array
    {
        $requestMessage = new RequestMessage($this->subscription->getId(), [$filter]);
        $request = new Request($this->relay, $requestMessage);
        return $request->send();
    }

    /**
     * Validates basic response structure from relay where the last response message always should be an EOSE.
     */
    private function assertValidRelayResponse(array $result): void
    {
        $this->assertNotEmpty($result, 'Results of request should not be empty');
        $this->assertArrayHasKey(self::RELAY_URL, $result);
        $this->assertInstanceOf(RelayResponseEose::class, end($result[self::RELAY_URL]));
    }

    /**
     * Validates an event matches expected type flags.
     */
    private function assertEventTypeFlags(Event $event, bool $isRegular, bool $isReplaceable, bool $isEphemeral, bool $isAddressable): void
    {
        $this->assertEquals($isRegular, $event->isRegular(), 'Unexpected regular flag');
        $this->assertEquals($isReplaceable, $event->isReplaceable(), 'Unexpected replaceable flag');
        $this->assertEquals($isEphemeral, $event->isEphemeral(), 'Unexpected ephemeral flag');
        $this->assertEquals($isAddressable, $event->isAddressable(), 'Unexpected addressable flag');
    }

    /**
     * @dataProvider eventKindProvider
     */
    public function testEventTypes(int $kind, bool $isRegular, bool $isReplaceable, bool $isEphemeral, bool $isAddressable): void
    {
        $filter = $this->createFilter([$kind]);
        $result = $this->sendRequest($filter);

        $this->assertValidRelayResponse($result);

        foreach ($result[self::RELAY_URL] as $response) {
            if ($response instanceof RelayResponseEvent && isset($response->event)) {
                $event = new Event();
                $event->populate($response->event);
                $this->assertEventTypeFlags($event, $isRegular, $isReplaceable, $isEphemeral, $isAddressable);
            }
        }
    }

    public static function eventKindProvider(): array
    {
        return [
            'regular_event' => [20, true, false, false, false],
            'replaceable_event' => [10002, false, true, false, false],
            'ephemeral_event' => [22242, false, false, true, false],
            'addressable_event' => [30023, false, false, false, true],
        ];
    }

    /**
     * Tests basic request functionality to a relay.
     */
    public function testBasicRequestToRelay(): void
    {
        $filter = $this->createFilter([1]);
        $result = $this->sendRequest($filter);

        $this->assertValidRelayResponse($result);
        $this->assertInstanceOf(RelayResponseEvent::class, $result[self::RELAY_URL][0]);
    }

    /**
     * Tests creating a mock ephemeral event.
     */
    public function testMockEphemeralEvent(): void
    {
        // Create mock ephemeral event
        $mockEvent = new Event();
        $mockEvent->setKind(22242)
            ->setCreatedAt(time())
            ->setContent('Test ephemeral event')
            ->setPublicKey(self::TEST_PUBKEY)
            ->setTags([['t', 'test']]);

        // Validate mock event
        $this->assertEventTypeFlags(
            $mockEvent,
            isRegular: false,
            isReplaceable: false,
            isEphemeral: true,
            isAddressable: false,
        );
    }

    /**
     * Tests response structure and validation.
     */
    public function testResponseValidation(): void
    {
        $filter = $this->createFilter([1]);
        $result = $this->sendRequest($filter);

        foreach ($result[self::RELAY_URL] as $response) {
            $this->assertInstanceOf(RelayResponse::class, $response);

            if ($response instanceof RelayResponseEvent) {
                $this->assertIsObject($response->event);

                // Validate all required event properties exist
                $requiredProperties = ['id', 'pubkey', 'created_at', 'kind', 'tags', 'content', 'sig'];
                foreach ($requiredProperties as $property) {
                    $this->assertTrue(
                        property_exists($response->event, $property),
                        "Event should have property '$property'",
                    );
                }

                // Validate property types
                $this->assertIsString($response->event->id, 'Event id should be string');
                $this->assertIsString($response->event->pubkey, 'Event pubkey should be string');
                $this->assertIsInt($response->event->created_at, 'Event created_at should be int');
                $this->assertIsInt($response->event->kind, 'Event kind should be int');
                $this->assertIsArray($response->event->tags, 'Event tags should be array');
                $this->assertIsString($response->event->content, 'Event content should be string');
                $this->assertIsString($response->event->sig, 'Event signature should be string');
            }
        }
    }
}
