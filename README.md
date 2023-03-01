# nostr-php

PHP Helper library for Nostr.
More info about Nostr: https://github.com/nostr-protocol/nostr

## Signing an event

Generates the id and signature for an event. The 'id' and 'sig' properties are 
added to the array.

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
$event = $signer->signEvent($event, $private_key);
```

## Generate event message

Generates ["EVENT", <event JSON as created above with id and sig>]

```
$signer = new Sign();
$event = $signer->signEvent($event, $private_key);
$message = $signer->generateEvent($event);
```

## Converting keys

Convert bech32 encoded keys (npub, nsec) to hex.

```
$public_key = 'npub10elfcs4fr0l0r8af98jlmgdh9c8tcxjvz9qkw038js35mp4dma8qzvjptg';
$keys = new Keys();
$hex = $keys->convertToHex($public_key);
```

Convert hex keys to bech32 (npub, nsec).

```
$public_key = '7e7e9c42a91bfef19fa929e5fda1b72e0ebc1a4c1141673e2794234d86addf4e';
$private_key = '67dea2ed018072d675f5415ecfaed7d2597555e202d85b3d65ea4e58d2d92ffa';
$keys = new Keys();
$bech32_public = $keys->convertPublicKeyToBech32($public_key);
$bech32_private = $keys->convertPrivateKeyToBech32($private_key);
```