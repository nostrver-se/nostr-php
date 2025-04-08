<?php

declare(strict_types=1);

namespace swentel\nostr\Nip19;

/**
 * https://github.com/nostriphant/nip-19/blob/main/src/Checksum.php
 */
class Checksum
{
    public const CHECKSUM_LENGTH = 6;

    public function __construct(private string $hrp, private array $words) {}

    public function __invoke(callable $encoder, int $length = self::CHECKSUM_LENGTH): string
    {
        $polyMod = new PolyMod($this->hrp, $this->words);
        $polyModChecksum = $polyMod->createChecksumFor($polyMod, $length)() ^ 1;
        $results = [];
        for ($i = 0; $i < $length; $i++) {
            $results[$i] = ($polyModChecksum >> 5 * (5 - $i)) & 31;
        }

        return "{$this->hrp}1" . array_reduce(array_merge($this->words, $results), $encoder, '');
    }

    /**
     *
     *
     * @param string $hrp
     * @param array $data
     * @param int $length
     * @return array|false
     */
    public static function validate(string $hrp, array $data, int $length = self::CHECKSUM_LENGTH): bool|array
    {
        if ((new PolyMod($hrp, $data))() !== 1) {
            return false;
        }
        return array_slice($data, 0, -$length);
    }
}
