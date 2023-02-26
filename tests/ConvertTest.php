<?php

namespace swentel\nostr;

use PHPUnit\Framework\TestCase;

class ConvertTest extends TestCase
{
    /**
     * Tests key conversion.
     */
    public function testKeyConversion()
    {

        $public_key_bech32 = 'npub10elfcs4fr0l0r8af98jlmgdh9c8tcxjvz9qkw038js35mp4dma8qzvjptg';
        $public_key_hex = '7e7e9c42a91bfef19fa929e5fda1b72e0ebc1a4c1141673e2794234d86addf4e';

        $this->assertEquals(
          $public_key_hex,
          Keys::convertPublicKeyToHex($public_key_bech32),
        );

        $private_key_bech32 = 'nsec1vl029mgpspedva04g90vltkh6fvh240zqtv9k0t9af8935ke9laqsnlfe5';
        $private_key_hex = '67dea2ed018072d675f5415ecfaed7d2597555e202d85b3d65ea4e58d2d92ffa';

        $this->assertEquals(
          $private_key_hex,
          Keys::convertPrivateKeyToHex($private_key_bech32),
        );

    }

}
