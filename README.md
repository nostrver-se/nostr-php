# nostr-php

PHP Helper library for Nostr.
More info about Nostr: https://github.com/nostr-protocol/nostr

To use in your project: `composer require swentel/nostr-php`

## Generating a private key and a public key

```php
use Keys;

$keys = new Keys();

$private_key = $keys->generatePrivateKey();
$public_key  = $keys->getPublicKey($private_key);

```

## Signing an event

Generates the id and signature for an event. The 'id' and 'sig' properties are
added to the array.

```php

$event = [
  'pubkey' => $public_key,
  'created_at' => time(),
  'kind' => 1,
  'tags' => [],
  'content' => 'This is a message',
];
$signer = new Sign();
$event = $signer->signEvent($event, $private_key);
```

## Creating, signing and preparing events to be send

Generates `["EVENT", <event JSON as created above with id and sig>]`

```php
$signer = new Sign();
$event = $signer->signEvent($event, $private_key);
$envelope = $signer->generateEvent($event);
```

## Interacting with a relay

Publish an event that has been prepared for publishing as `$envelope` to a relay.

```php
$websocket = 'wss://nostr-websocket.tld';
$relay = new Relay($websocket);

$result = $relay->publish($envelope);
```

## Converting keys

Convert bech32 encoded keys (npub, nsec) to hex.

```php
$public_key = 'npub10elfcs4fr0l0r8af98jlmgdh9c8tcxjvz9qkw038js35mp4dma8qzvjptg';
$keys = new Keys();
$hex = $keys->convertToHex($public_key);
```

Convert hex keys to bech32 (npub, nsec).

```php
$public_key = '7e7e9c42a91bfef19fa929e5fda1b72e0ebc1a4c1141673e2794234d86addf4e';
$private_key = '67dea2ed018072d675f5415ecfaed7d2597555e202d85b3d65ea4e58d2d92ffa';
$keys = new Keys();
$bech32_public = $keys->convertPublicKeyToBech32($public_key);
$bech32_private = $keys->convertPrivateKeyToBech32($private_key);
```
