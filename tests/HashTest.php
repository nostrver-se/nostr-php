<?php

namespace swentel\nostr;

use PHPUnit\Framework\TestCase;

class HashTest extends TestCase
{
    /**
     * Tests hash generation.
     */
    public function testHashGenerate()
    {
        $sign = new Sign();
        $arrays = [];
        $arrays['[0,"value"]'] = ['value'];
        $arrays['[0,"value",123]'] = ['value', 123];
        $arrays['[0,"value",123,[]]'] = ['value', 123, []];
        $arrays['[0,"value",123,[],"another value"]'] = ['value', 123, [], 'another value'];
        $arrays['[0,"value",123,1,[],"another value"]'] = ['value', 123, 1, [], 'another value'];
        $arrays['[0,"value","with keys"]'] = ['key' => 'value', 'key2' => "with keys"];
        $arrays['[0,"value","Content\n\nwith new lines\nAnd quotes: \'"]'] = ['key' => 'value', 'key2' => "Content\n\nwith new lines\nAnd quotes: '"];
        $arrays['[0,"value","https://example.com/url"]'] = ['key' => 'value', 'key2' => "https://example.com/url"];

        foreach ($arrays as $expected => $source)
        {
            $this->assertEquals(
                $expected,
                $sign->generateHash($source),
            );
        }
    }

}