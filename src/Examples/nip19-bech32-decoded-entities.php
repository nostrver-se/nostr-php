<?php

declare(strict_types=1);

require __DIR__ . '/../../vendor/autoload.php';

use swentel\nostr\Nip19\Nip19Helper;

/**
 * Example snippets where we decode bech32 formatted entities into arrays.
 */

try {
    $nip19 = new Nip19Helper(); // The NIP-19 helper class.

    echo print_r($nip19->decode('note1aek4l6f853lxz0gzxne0swnf5fmkwg29ptzqqzcmy9kch375u2jqqygewz'), true) . PHP_EOL;
    echo print_r($nip19->decodeNote('note1aek4l6f853lxz0gzxne0swnf5fmkwg29ptzqqzcmy9kch375u2jqqygewz'), true) . PHP_EOL;

    // Decode nevent bech32 encoded string with TLV items (relays, author)
    echo print_r($nip19->decode('nevent1qqsy87cyyfzhc8ada35vttgcx79tktrzd44hsausjulg3rgfnrmva4qprpmhxue69uhkummnw3ezuum9vfshxarf0qhxgetkqgsqvcu68pkfcyq5y9mz9n9u7sys33835rpnuglc6mtg7j4lv40c7ugdggh4t'), true) . PHP_EOL;

    // Decode nevent bech32 encoded string with TLV items (relays, author and kind)
    echo print_r($nip19->decode('nevent1qqsy87cyyfzhc8ada35vttgcx79tktrzd44hsausjulg3rgfnrmva4qprpmhxue69uhkummnw3ezuum9vfshxarf0qhxgetkqgsqvcu68pkfcyq5y9mz9n9u7sys33835rpnuglc6mtg7j4lv40c7ugrqsqqqqqpwtv9ev'), true) . PHP_EOL;

    // Decode naddr bech32 encoded string with TLV items (identifier, author and kind)
    echo print_r($nip19->decode('naddr1qq8hwet9dvknzvfdw4cxgct5v4esygqxvwdrsmyuzq2zza3zej70gzggcnc6pse7y0udd450f2lk2hu0wypsgqqqw4rs35j8dj'), true) . PHP_EOL;

    // Decode naddr bech32 encoded string with TLV items (identifier, author, relays and kind)
    echo print_r($nip19->decode('naddr1qq8hwet9dvknzvfdw4cxgct5v4eszxrhwden5te0dehhxarj9eek2cnpwd6xj7pwv3jhvq3qqe3e5wrvnsgpggtkytxteaqfprz0rgxr8c3l34kk3a9t7e2l3acsxpqqqp65wnt7dt6'), true) . PHP_EOL;

    // Decode nprofile bech32 encoded string with TLV items (pubkey, relays)
    echo print_r($nip19->decode('nprofile1qqsqvcu68pkfcyq5y9mz9n9u7sys33835rpnuglc6mtg7j4lv40c7ugprpmhxue69uhkummnw3ezuum9vfshxarf0qhxgetkqyg8wumn8ghj7mn0wd68ytnhd9hx2c4r37c'), true) . PHP_EOL;

} catch (Exception $e) {
    print $e->getMessage() . PHP_EOL;
}
