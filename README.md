# nostr-php

PHP Helper library for Nostr.
More info about Nostr: https://github.com/nostr-protocol/nostr

## Signing an event

Calculates the id and signs an event. The 'id' and 'sig' properties are added
to the array.

```
$private_key = 'yourprivatekey';

$event = [
  'pubkey' => $public_key,
  'created_at' => time(),
  'kind' => 1,
  'tags' => [],
  'content' => trim($entity->get('body')->value),
];
$signer = new Sign();
$event = $signer->sign($event, $private_key);
```

## Converting keys

Convert bech32 encoded keys (npub, nsec) to hex.

```
$public_key = '7e7e9c42a91bfef19fa929e5fda1b72e0ebc1a4c1141673e2794234d86addf4e';
$keys = new Keys();
$hex = $keys->convertKeyToHex($public_key);
```