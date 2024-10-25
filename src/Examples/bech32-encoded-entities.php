<?php

declare(strict_types=1);

require __DIR__ . '/../../vendor/autoload.php';

use swentel\nostr\Key\Key;
use swentel\nostr\Nip19\Nip19Helper;

try {
    $nip19 = new Nip19Helper(); // Helper.
    $id = '43fb0422457c1fadec68c5ad18378abb2c626d6b787790973e888d0998f6ced4'; // This is an event hex id.
    //$id = 'fb0422457c1fadec68c5ad18378abb2c626d6b787790973e888d0998f6ce'; // Invalid ID.

    // Encode it to a bech32 encoded note ID.
    $note = $nip19->encodeNote($id);
    // Expected result:
    // note1g0asggj90s06mmrgckk3sdu2hvkxymtt0pmep9e73zxsnx8kem2qulye77
    print $note . PHP_EOL;

    // Encode a profile pubkey or npub, this already works.
    $key = new Key();
    $pubkey = '06639a386c9c1014217622ccbcf40908c4f1a0c33e23f8d6d68f4abf655f8f71';
    // Alternative: $npub = $key->convertPublicKeyToBech32($pubkey);
    $npub = $nip19->encodeNpub($pubkey);
    // Expected result:
    // npub1qe3e5wrvnsgpggtkytxteaqfprz0rgxr8c3l34kk3a9t7e2l3acslezefe
    print $npub . PHP_EOL;

    // Using the more generic encode method
    $note1 = $nip19->encode($id, 'note');
    print $note1 . PHP_EOL;

    // TODO:
    // Encode to nevent with TLV data

    // Encode it bech32 encoded nevent ID,
    // $nevent = $nip19->encodeEvent($id);
    // Expected result:
    // nevent1qqsy87cyyfzhc8ada35vttgcx79tktrzd44hsausjulg3rgfnrmva4qey0p0j
    // print $nevent . PHP_EOL;

    // Encode to nprofile with TLV data

    // Encode to naddr with TLV data

    // Decode a bech32 encoded entity to an event ID.
    $nevent = '';

} catch (Exception $e) {
    print $e->getMessage() . PHP_EOL;
}
