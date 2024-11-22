<?php

declare(strict_types=1);

require __DIR__ . '/../../vendor/autoload.php';

use swentel\nostr\Key\Key;
use swentel\nostr\Nip19\Nip19Helper;

/**
 * Example snippet where we encode key ands ids into bech32 formatted entities.
 */

try {
    $nip19 = new Nip19Helper(); // The helper.
    $event_id = '43fb0422457c1fadec68c5ad18378abb2c626d6b787790973e888d0998f6ced4'; // This is an event hex id.
    //$id = 'fb0422457c1fadec68c5ad18378abb2c626d6b787790973e888d0998f6ce'; // This is an invalid ID.

    // Encode it to a bech32 encoded note ID.
    $note = $nip19->encodeNote($event_id);
    // Expected result:
    // note1g0asggj90s06mmrgckk3sdu2hvkxymtt0pmep9e73zxsnx8kem2qulye77
    print $note . PHP_EOL;
    // Alternative: using the more generic encode method with the encode() method.
    $note1 = $nip19->encode($event_id, 'note');
    //print $note1 . PHP_EOL;

    // Encode a profile pubkey or npub, this already works.
    $pubkey = '06639a386c9c1014217622ccbcf40908c4f1a0c33e23f8d6d68f4abf655f8f71';
    // Alternative way:
    // $key = new Key();
    // $npub = $key->convertPublicKeyToBech32($pubkey);
    $npub = $nip19->encodeNpub($pubkey);
    // Expected result:
    // npub1qe3e5wrvnsgpggtkytxteaqfprz0rgxr8c3l34kk3a9t7e2l3acslezefe
    print $npub . PHP_EOL;

    // TODO
    // Encode to nevent with TLV data
    $nevent_1 = $nip19->encodeEvent($event_id);
    $nevent_11 = $nip19->encode($event_id, 'nevent');
    // Expected result, checked with nak:
    // $ ./nak encode nevent 43fb0422457c1fadec68c5ad18378abb2c626d6b787790973e888d0998f6ced4
    // nevent1qqsy87cyyfzhc8ada35vttgcx79tktrzd44hsausjulg3rgfnrmva4qey0p0j
    print $nevent_1 . PHP_EOL;

    // TODO
    $nevent_2 = $nip19->encodeEvent($event_id, [], $pubkey, 1);
    // Expected result, checked with nak:
    // $ ./nak encode nevent --author 06639a386c9c1014217622ccbcf40908c4f1a0c33e23f8d6d68f4abf655f8f71 43fb0422457c1fadec68c5ad18378abb2c626d6b787790973e888d0998f6ced4
    // nevent1qqsy87cyyfzhc8ada35vttgcx79tktrzd44hsausjulg3rgfnrmva4qzyqrx8x3cdjwpq9ppwc3ve085pyyvfudqcvlz87xk668540m9t78hz5s5hp9
    print $nevent_2 . PHP_EOL;

    // TODO
    $pubkey = 'npub1qe3e5wrvnsgpggtkytxteaqfprz0rgxr8c3l34kk3a9t7e2l3acslezefe'; // This npub will be converted to a hex formatted pubkey.
    $relays = ['wss://nostr.sebastix.dev'];
    //$nevent_3 = $nip19->encodeEvent($event_id, $relays, $pubkey, 1);
    // Expected result, checked with nak:
    // $ ./nak encode nevent --author 06639a386c9c1014217622ccbcf40908c4f1a0c33e23f8d6d68f4abf655f8f71 --relay wss://nostr.sebastix.dev 43fb0422457c1fadec68c5ad18378abb2c626d6b787790973e888d0998f6ced4
    // nevent1qqsy87cyyfzhc8ada35vttgcx79tktrzd44hsausjulg3rgfnrmva4qprpmhxue69uhkummnw3ezuum9vfshxarf0qhxgetkqgsqvcu68pkfcyq5y9mz9n9u7sys33835rpnuglc6mtg7j4lv40c7ugdggh4t
    //print $nevent_3 . PHP_EOL;

    // TODO
    // Encode to nprofile with TLV data
    $pubkey = '3bf0c63fcb93463407af97a5e5ee64fa883d107ef9e558472c4eb9aaaefa459d';
    $relays = ['wss://r.x.com', 'wss://djbas.sadkb.com'];
    //$nprofile = $nip19->encodeProfile($pubkey, $relays);
    // Expected result with TLV items:
    // - pubkey: 3bf0c63fcb93463407af97a5e5ee64fa883d107ef9e558472c4eb9aaaefa459d
    // - relay: wss://r.x.com
    // - relay: wss://djbas.sadkb.com
    // $ ./nak encode nprofile --relay wss://r.x.com --relay wss://djbas.sadkb.com 3bf0c63fcb93463407af97a5e5ee64fa883d107ef9e558472c4eb9aaaefa459d
    // nprofile1qqsrhuxx8l9ex335q7he0f09aej04zpazpl0ne2cgukyawd24mayt8gpp4mhxue69uhhytnc9e3k7mgpz4mhxue69uhkg6nzv9ejuumpv34kytnrdaksjlyr9p

    // TODO
    // Encode to naddr with TLV data

    // TODO
    // Decode a bech32 encoded event entity to an event ID.
    $nevent = '';

    // TODO
    // Decode a bech32 encoded profile entity with TLV data
    $profile_id = 'nprofile1qqsrhuxx8l9ex335q7he0f09aej04zpazpl0ne2cgukyawd24mayt8gpp4mhxue69uhhytnc9e3k7mgpz4mhxue69uhkg6nzv9ejuumpv34kytnrdaksjlyr9p';
    // Expected result with TLV items:
    // - pubkey: 3bf0c63fcb93463407af97a5e5ee64fa883d107ef9e558472c4eb9aaaefa459d
    // - relay: wss://r.x.com
    // - relay: wss://djbas.sadkb.com

} catch (Exception $e) {
    print $e->getMessage() . PHP_EOL;
}
