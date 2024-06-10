<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use swentel\nostr\Event\Event;
use swentel\nostr\Message\EventMessage;

class VerifyTest extends TestCase
{
    public function testValidEventValidation()
    {
        // Plain json string.
        $json = '{"id":"ac21a6c4cb128a27c0b9a229bc1fa8e7167660664354d5e5a481825d01188108","pubkey":"7543c184ff776be3c13d2437894494173cfea4e9919d48fb2934216a13a53c58","created_at":1697800339,"kind":1,"tags":[["e","5361bb83c899cf75589a25c32cea5b868d4990da41fd4dba84144eede5ad1359"],["p","d4dea80c64ebd3f9bc8271893191dbc851ecd2b7bcb811bb87386b5158ee735d"]],"content":"Gm ☕️⚡️🌅","sig":"46a9d4f4470bdf685d4fc4f664d1d4c8576e501cfe0ccf044ba65d36c3ee66eab3f261d524a4bdbde71bc2d921c13829a5ac596538ded74125f5060d4f3f805d"}';
        $event = new Event();
        $this->assertTrue($event->verify($json));

        // Same as the plain string but then as an object.
        $event = new Event();
        $event->setId('ac21a6c4cb128a27c0b9a229bc1fa8e7167660664354d5e5a481825d01188108');
        $event->setPublicKey('7543c184ff776be3c13d2437894494173cfea4e9919d48fb2934216a13a53c58');
        $event->setKind(1);
        $event->setCreatedAt(1697800339);
        $event->setTags([
            ['e', '5361bb83c899cf75589a25c32cea5b868d4990da41fd4dba84144eede5ad1359'],
            ['p', 'd4dea80c64ebd3f9bc8271893191dbc851ecd2b7bcb811bb87386b5158ee735d'],
        ]);
        $event->setContent('Gm ☕️⚡️🌅');
        $event->setSignature('46a9d4f4470bdf685d4fc4f664d1d4c8576e501cfe0ccf044ba65d36c3ee66eab3f261d524a4bdbde71bc2d921c13829a5ac596538ded74125f5060d4f3f805d');
        $this->assertTrue($event->verify());
    }

    /**
     * @dataProvider invalidEventsProvider
     */
    public function testInvalidEventsValidation(string $json)
    {
        $event = new Event();
        $this->assertFalse($event->verify($json));
    }

    public static function invalidEventsProvider(): array
    {
        return [
            'not json'          => ['Craig Wright is not Satoshi Nakamoto'],
            'non object'        => ['[1,2,3'],
            'empty json'        => ['{}'],
            'meaningless json'  => ['{"foo":"bar"}'],
            'missing fields'    => ['{"id":"ac21a6c4cb128a27c0b9a229bc1fa8e7167660664354d5e5a481825d01188108","pubkey":"7543c184ff776be3c13d2437894494173cfea4e9919d48fb2934216a13a53c58","created_at":1697800339,"kind":1}'],
            'invalid tag'       => ['{"id":"ac21a6c4cb128a27c0b9a229bc1fa8e7167660664354d5e5a481825d01188108","pubkey":"7543c184ff776be3c13d2437894494173cfea4e9919d48fb2934216a13a53c58","created_at":1697800339,"kind":1,"tags":[1234,["p","d4dea80c64ebd3f9bc8271893191dbc851ecd2b7bcb811bb87386b5158ee735d"]],"content":"Gm ☕️⚡️🌅","sig":"46a9d4f4470bdf685d4fc4f664d1d4c8576e501cfe0ccf044ba65d36c3ee66eab3f261d524a4bdbde71bc2d921c13829a5ac596538ded74125f5060d4f3f805d"}'],
            'invalid tag value' => ['{"id":"ac21a6c4cb128a27c0b9a229bc1fa8e7167660664354d5e5a481825d01188108","pubkey":"7543c184ff776be3c13d2437894494173cfea4e9919d48fb2934216a13a53c58","created_at":1697800339,"kind":1,"tags":[[123,"5361bb83c899cf75589a25c32cea5b868d4990da41fd4dba84144eede5ad1359"],["p","d4dea80c64ebd3f9bc8271893191dbc851ecd2b7bcb811bb87386b5158ee735d"]],"content":"Gm ☕️⚡️🌅","sig":"46a9d4f4470bdf685d4fc4f664d1d4c8576e501cfe0ccf044ba65d36c3ee66eab3f261d524a4bdbde71bc2d921c13829a5ac596538ded74125f5060d4f3f805d"}'],
            'invalid id'        => ['{"id":"bc21a6c4cb128a27c0b9a229bc1fa8e7167660664354d5e5a481825d01188108","pubkey":"7543c184ff776be3c13d2437894494173cfea4e9919d48fb2934216a13a53c58","created_at":1697800339,"kind":1,"tags":[["e","5361bb83c899cf75589a25c32cea5b868d4990da41fd4dba84144eede5ad1359"],["p","d4dea80c64ebd3f9bc8271893191dbc851ecd2b7bcb811bb87386b5158ee735d"]],"content":"Gm ☕️⚡️🌅","sig":"46a9d4f4470bdf685d4fc4f664d1d4c8576e501cfe0ccf044ba65d36c3ee66eab3f261d524a4bdbde71bc2d921c13829a5ac596538ded74125f5060d4f3f805d"}'],
            'invalid sig'       => ['{"id":"ac21a6c4cb128a27c0b9a229bc1fa8e7167660664354d5e5a481825d01188108","pubkey":"7543c184ff776be3c13d2437894494173cfea4e9919d48fb2934216a13a53c58","created_at":1697800339,"kind":1,"tags":[["e","5361bb83c899cf75589a25c32cea5b868d4990da41fd4dba84144eede5ad1359"],["p","d4dea80c64ebd3f9bc8271893191dbc851ecd2b7bcb811bb87386b5158ee735d"]],"content":"Gm ☕️⚡️🌅","sig":"56a9d4f4470bdf685d4fc4f664d1d4c8576e501cfe0ccf044ba65d36c3ee66eab3f261d524a4bdbde71bc2d921c13829a5ac596538ded74125f5060d4f3f805d"}'],
        ];
    }
}
