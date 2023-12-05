<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use swentel\nostr\Event\Event;
use swentel\nostr\Sign\Sign;

class SerializeTest extends TestCase
{
    /**
     * Tests serializing event..
     */
    public function testSerializeEvent()
    {
        $time = time();
        $public_key = '7e7e9c42a91bfef19fa929e5fda1b72e0ebc1a4c1141673e2794234d86addf4e';

        $sign = new Sign();
        $arrays = [];
        $arrays['[0,"' . $public_key . '",' . $time . ',1,[],"Content\n\nwith new lines\nAnd quotes: \'"]'] = ['content' => "Content\n\nwith new lines\nAnd quotes: '"];
        $arrays['[0,"' . $public_key . '",' . $time . ',1,[],"https://example.com/url"]'] = ['content' => "https://example.com/url"];

        foreach ($arrays as $expected => $source) {
            $note = new Event();
            $note->setCreatedAt($time);
            $note->setPublicKey($public_key);
            $note->setKind(1);
            if (isset($source['content'])) {
                $note->setContent($source['content']);
            }

            $this->assertEquals(
                $expected,
                $sign->serializeEvent($note),
            );
        }
    }
}
