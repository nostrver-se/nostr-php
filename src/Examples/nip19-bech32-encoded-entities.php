<?php

declare(strict_types=1);

require __DIR__ . '/../../vendor/autoload.php';

use swentel\nostr\Event\Event;
use swentel\nostr\Event\Profile\Profile;
use swentel\nostr\Nip19\Nip19Helper;

/**
 * Example snippets where we encode key ands ids into bech32 formatted entities.
 */

try {
    $nip19 = new Nip19Helper(); // The NIP-19 helper class.
    $event_id = '43fb0422457c1fadec68c5ad18378abb2c626d6b787790973e888d0998f6ced4'; // This is an event hex id.

    /*
     * Encode event id string to a bech32 encoded note ID.
     */
    $note = $nip19->encodeNote($event_id);
    // Expected result:
    // note1g0asggj90s06mmrgckk3sdu2hvkxymtt0pmep9e73zxsnx8kem2qulye77
    print $note . PHP_EOL;
    // Alternative: using the more generic encode method with the encode() method.
    $note1 = $nip19->encode($event_id, 'note');
    print $note1 . PHP_EOL;

    /*
     * Encode a profile pubkey or npub.
     */
    $pubkey = '06639a386c9c1014217622ccbcf40908c4f1a0c33e23f8d6d68f4abf655f8f71';
    // Alternative way:
    // $key = new Key();
    // $npub = $key->convertPublicKeyToBech32($pubkey);
    $npub = $nip19->encodeNpub($pubkey);
    // Expected result:
    // npub1qe3e5wrvnsgpggtkytxteaqfprz0rgxr8c3l34kk3a9t7e2l3acslezefe
    print $npub . PHP_EOL;

    /*
     * Encode an event to bech32 encoded string with prefix `nevent`.
     */
    $event = new Event();
    $event->setId($event_id);
    $event->setKind(1);
    $event->setPublicKey('06639a386c9c1014217622ccbcf40908c4f1a0c33e23f8d6d68f4abf655f8f71');

    /*
     * Encode event to nevent without TLV data.
     */
    $nevent_1 = $nip19->encode($event, 'nevent');
    // Result checked with nak:
    // $ ./nak encode nevent 43fb0422457c1fadec68c5ad18378abb2c626d6b787790973e888d0998f6ced4
    // nevent1qqsy87cyyfzhc8ada35vttgcx79tktrzd44hsausjulg3rgfnrmva4qey0p0j
    // Result checked with https://njump.me/43fb0422457c1fadec68c5ad18378abb2c626d6b787790973e888d0998f6ced4
    // nevent1qqsy87cyyfzhc8ada35vttgcx79tktrzd44hsausjulg3rgfnrmva4qzyqrx8x3cdjwpq9ppwc3ve085pyyvfudqcvlz87xk668540m9t78hzqcyqqqqqqg8t6tum
    print $nevent_1 . PHP_EOL;
    // Same result with specific encodeEvent method.
    $nevent_11 = $nip19->encodeEvent($event);
    print $nevent_11 . PHP_EOL;

    /*
     * Encode an event to bech32 encoded string with prefix `nevent` with TLV data.
     */
    $pubkey = '06639a386c9c1014217622ccbcf40908c4f1a0c33e23f8d6d68f4abf655f8f71';
    $relays = ['wss://nostr.sebastix.dev'];
    $nevent_2 = $nip19->encodeEvent($event, $relays ?? [], $pubkey, 1);
    // Expected result, checked with nak:
    // $ ./nak encode nevent --relay wss://nostr.sebastix.dev --author 06639a386c9c1014217622ccbcf40908c4f1a0c33e23f8d6d68f4abf655f8f71 43fb0422457c1fadec68c5ad18378abb2c626d6b787790973e888d0998f6ced4
    // nevent1qqsy87cyyfzhc8ada35vttgcx79tktrzd44hsausjulg3rgfnrmva4qprpmhxue69uhkummnw3ezuum9vfshxarf0qhxgetkqgsqvcu68pkfcyq5y9mz9n9u7sys33835rpnuglc6mtg7j4lv40c7ugdggh4t
    print $nevent_2 . PHP_EOL;

    /*
     * Encode an event to bech32 encoded string with prefix `naddr` with TLV data.
     */
    $event_kind30023_id = 'dfe2bc7f5da5fe2b5e3083a9856b68d29b65b59d4503e081292a27b6e7438b56';
    $event = new Event();
    $event->setId($event_kind30023_id);
    $event->setKind(30023);
    $event->setPublicKey('06639a386c9c1014217622ccbcf40908c4f1a0c33e23f8d6d68f4abf655f8f71');
    $dTag = 'week-11-updates';
    $event->addTag(['d', $dTag]);
    $relays = ['wss://nostr.sebastix.dev'];
    $naddr = $nip19->encodeAddr($event, $dTag, 30023, '06639a386c9c1014217622ccbcf40908c4f1a0c33e23f8d6d68f4abf655f8f71', $relays);
    // Expected result with TLV items:
    // - identifier week-11-updates
    // - relay wss://nostr.sebastix.dev
    // - pubkey: 06639a386c9c1014217622ccbcf40908c4f1a0c33e23f8d6d68f4abf655f8f71
    // - kind: 30023
    print $naddr . PHP_EOL;

    /*
     * Encode a profile to a bech32 formatted string.
     */
    $pubkey = '06639a386c9c1014217622ccbcf40908c4f1a0c33e23f8d6d68f4abf655f8f71';
    $profile = new Profile();
    $profile = $profile->fetch($pubkey);
    $nprofile = $nip19->encodeProfile($profile);
    // Expected result:
    // $ ./nak encode nprofile 06639a386c9c1014217622ccbcf40908c4f1a0c33e23f8d6d68f4abf655f8f71
    // nprofile1qqsqvcu68pkfcyq5y9mz9n9u7sys33835rpnuglc6mtg7j4lv40c7ugfe0raa
    print $nprofile . PHP_EOL;
    // with TLV items:
    // - relay: wss://nostr.sebastix.dev
    // - relay: wss://nostr.wine
    $relays = [
        'wss://nostr.sebastix.dev',
        'wss://nostr.wine',
    ];
    $nprofile1 = $nip19->encodeProfile($profile, $relays);
    // Expected result:
    // $ ./nak encode nprofile 06639a386c9c1014217622ccbcf40908c4f1a0c33e23f8d6d68f4abf655f8f71 --relay wss://nostr.sebastix.dev --relay wss://nostr.wine
    // nprofile1qqsqvcu68pkfcyq5y9mz9n9u7sys33835rpnuglc6mtg7j4lv40c7ugprpmhxue69uhkummnw3ezuum9vfshxarf0qhxgetkqyg8wumn8ghj7mn0wd68ytnhd9hx2c4r37c
    print $nprofile1 . PHP_EOL;
    // Same result as example above.
    print $nip19->encode($profile, 'nprofile', ['relays' => $relays]) . PHP_EOL;
} catch (Exception $e) {
    print $e->getMessage() . PHP_EOL;
}
