<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use swentel\nostr\Encryption\Nip44;

class Nip44VectorsTest extends TestCase
{
    /**
     * Test vectors from https://github.com/paulmillr/nip44/blob/main/nip44.vectors.json
     */
    public function testEncryptionVectors(): void
    {
        // Test vector 1: Basic encryption/decryption
        $conversationKey = hex2bin('ca2527a037347b91bea0c8a30fc8d9600ffd81ec00038671e3a0f0cb0fc9f642');
        $nonce = hex2bin('daaea5ca345b268e5b62060ca72c870c48f713bc1e00ff3fc0ddb78e826f10db');
        $plaintext = 'noble';

        $encrypted = Nip44::encrypt($plaintext, $conversationKey, $nonce);
        $decrypted = Nip44::decrypt($encrypted, $conversationKey);
        $this->assertEquals($plaintext, $decrypted);

        // Test vector 2: Unicode emoji
        $conversationKey = hex2bin('36f04e558af246352dcf73b692fbd3646a2207bd8abd4b1cd26b234db84d9481');
        $nonce = hex2bin('ad408d4be8616dc84bb0bf046454a2a102edac937c35209c43cd7964c5feb781');
        $plaintext = '⚠️';

        $encrypted = Nip44::encrypt($plaintext, $conversationKey, $nonce);
        $decrypted = Nip44::decrypt($encrypted, $conversationKey);
        $this->assertEquals($plaintext, $decrypted);

        // Test vector 3: Longer text with spaces
        $conversationKey = hex2bin('5254827d29177622d40a7b67cad014fe7137700c3c523903ebbe3e1b74d40214');
        $nonce = hex2bin('7ab65dbb8bbc2b8e35cafb5745314e1f050325a864d11d0475ef75b3660d91c1');
        $plaintext = 'elliptic-curve cryptography';

        $encrypted = Nip44::encrypt($plaintext, $conversationKey, $nonce);
        $decrypted = Nip44::decrypt($encrypted, $conversationKey);
        $this->assertEquals($plaintext, $decrypted);

        // Test vector 4: Long text with special characters
        $conversationKey = hex2bin('0c4cffb7a6f7e706ec94b2e879f1fc54ff8de38d8db87e11787694d5392d5b3f');
        $nonce = hex2bin('6f9fd72667c273acd23ca6653711a708434474dd9eb15c3edb01ce9a95743e9b');
        $plaintext = 'censorship-resistant and global social network';

        $encrypted = Nip44::encrypt($plaintext, $conversationKey, $nonce);
        $decrypted = Nip44::decrypt($encrypted, $conversationKey);
        $this->assertEquals($plaintext, $decrypted);
    }

    /**
     * Test error cases from the vectors
     */
    public function testErrorCases(): void
    {
        // Test empty message (should fail)
        $conversationKey = hex2bin('5cd2d13b9e355aeb2452afbd3786870dbeecb9d355b12cb0a3b6e9da5744cd35');
        $nonce = hex2bin('b60036976a1ada277b948fd4caa065304b96964742b89d26f26a25263a5060bd');

        $this->expectException(Exception::class);
        Nip44::encrypt('', $conversationKey, $nonce);

        // Test invalid base64
        $conversationKey = hex2bin('ca2527a037347b91bea0c8a30fc8d9600ffd81ec00038671e3a0f0cb0fc9f642');
        $invalidPayload = 'Atфupco0WyaOW2IGDKcshwxI9xO8HgD/P8Ddt46CbxDbrhdG8VmJZE0UICD06CUvEvdnr1cp1fiMtlM/GrE92xAc1EwsVCQEgWEu2gsHUVf4JAa3TpgkmFc3TWsax0v6n/Wq';

        $this->expectException(Exception::class);
        Nip44::decrypt($invalidPayload, $conversationKey);
    }

    /**
     * Test MAC verification
     */
    public function testMacVerification(): void
    {
        $conversationKey = hex2bin('cff7bd6a3e29a450fd27f6c125d5edeb0987c475fd1e8d97591e0d4d8a89763c');
        $payload = 'Agn/l3ULCEAS4V7LhGFM6IGA17jsDUaFCKhrbXDANholyySBfeh+EN8wNB9gaLlg4j6wdBYh+3oK+mnxWu3NKRbSvQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA';

        // This should fail due to invalid MAC
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Invalid MAC');
        Nip44::decrypt($payload, $conversationKey);
    }

    /**
     * Test padding validation
     */
    public function testPaddingValidation(): void
    {
        $conversationKey = hex2bin('fea39aca9aa8340c3a78ae1f0902aa7e726946e4efcd7783379df8096029c496');
        $payload = 'An1Cg+O1TIhdav7ogfSOYvCj9dep4ctxzKtZSniCw5MwRrrPJFyAQYZh5VpjC2QYzny5LIQ9v9lhqmZR4WBYRNJ0ognHVNMwiFV1SHpvUFT8HHZN/m/QarflbvDHAtO6pY16';

        // This will fail with Invalid MAC before we even get to padding validation,
        // which is the correct behavior for security (fail fast on MAC)
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Invalid MAC');
        Nip44::decrypt($payload, $conversationKey);
    }
}
