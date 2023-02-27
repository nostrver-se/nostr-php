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
$public_key = 'npub10elfcs4fr0l0r8af98jlmgdh9c8tcxjvz9qkw038js35mp4dma8qzvjptg';
$keys = new Keys();
$hex = $keys->convertKeyToHex($public_key);
```