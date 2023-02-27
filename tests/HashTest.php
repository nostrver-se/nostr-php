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

        foreach ($arrays as $expected => $source)
        {
            $this->assertEquals(
                $expected,
                $sign->generateHash($source),
            );
        }
    }

}