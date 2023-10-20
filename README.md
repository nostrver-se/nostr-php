# nostr-php

![CI](https://github.com/swentel/nostr-php/actions/workflows/ci.yml/badge.svg)
![Packagist PHP Version](https://img.shields.io/packagist/dependency-v/swentel/nostr-php/php)
![GitHub contributors](https://img.shields.io/github/contributors/swentel/nostr-php)
![GitHub issues](https://img.shields.io/github/issues/swentel/nostr-php)
![GitHub last commit (branch)](https://img.shields.io/github/last-commit/swentel/nostr-php/main)

This is a PHP Helper library for Nostr.
More info about Nostr: https://github.com/nostr-protocol/nostr.

If you need any help, please join this Telegram group: https://t.me/nostr_php

## Installation

To use in your project with Composer:

```console
$ composer require swentel/nostr-php

```

## Signing an event

Generates the id and signature for an event. The 'pubkey', 'id' and 'sig' 
properties are added to the event object.

```php
use swentel\nostr\Event\Event;
use swentel\nostr\Sign\Sign;

$note = new Event();
$note->setContent('Hello world!');
$note->setKind(1);

$signer = new Sign();
$signer->signEvent($event, $private_key);
```

## Generating a message

Generate an event message : `["EVENT", <event JSON as created above with id and sig>]`

```php
use swentel\nostr\Sign\Sign;
use swentel\nostr\Message\EventMessage;

$signer = new Sign();
$signer->signEvent($event, $private_key);
$eventMessage = new EventMessage($event);
$message_string = $eventMessage->generate();
```

## Interacting with a relay

Publish a note that has been prepared for sending to a Relay.

```php
use swentel\nostr\Event\Event;
use swentel\nostr\Message\EventMessage;
use swentel\nostr\Relay\Relay;

$note = new Event();
$note->setContent('Hello world');
$note->setKind(1);
$signer = new Sign();
$signer->signEvent($note, $private_key);
$eventMessage = new EventMessage($event);

$websocket = 'wss://nostr-websocket.tld';
$relay = new Relay($websocket, $eventMessage);
$result = $relay->send();
```

## Generating a private key and a public key

```php
use swentel\nostr\Key\Key;

$key = new Key();

$private_key = $key->generatePrivateKey();
$public_key  = $key->getPublicKey($private_key);

```

## Converting keys

Convert bech32 encoded keys (npub, nsec) to hex.

```php
use swentel\nostr\Key\Key;

$public_key = 'npub10elfcs4fr0l0r8af98jlmgdh9c8tcxjvz9qkw038js35mp4dma8qzvjptg';
$key = new Key();
$hex = $key->convertToHex($public_key);
```

Convert hex keys to bech32 (npub, nsec).

```php
use swentel\nostr\Key\Key;

$public_key = '7e7e9c42a91bfef19fa929e5fda1b72e0ebc1a4c1141673e2794234d86addf4e';
$private_key = '67dea2ed018072d675f5415ecfaed7d2597555e202d85b3d65ea4e58d2d92ffa';
$key = new Key();
$bech32_public = $key->convertPublicKeyToBech32($public_key);
$bech32_private = $key->convertPrivateKeyToBech32($private_key);
```

## nostr-php script

The library ships with a simple client to post a text note to a Nostr relay.

```bash
Usage:
nostr-php --content "Hello world!" --key /home/path/to/nostr-private.key --relay wss://nostr.pleb.network
```

Note: the key arguments expects a file with your private key! Do not paste your
private key on command line.

## Maintainers

* [@swentel](https://github.com/swentel) (original author)  `npub1z8n2zt0vzkefhrhpf60face4wwq2nx87sz7wlgcvuk4adddkkycqknzjk5`  
* [@sebastix](https://github.com/Sebastix)  `npub1qe3e5wrvnsgpggtkytxteaqfprz0rgxr8c3l34kk3a9t7e2l3acslezefe`
