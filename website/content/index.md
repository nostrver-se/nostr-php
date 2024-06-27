---
# https://vitepress.dev/reference/default-theme-home-page
layout: home

hero:
  name: "Nostr-PHP"
  text: "A PHP helper library for Nostr"
  tagline: Empower your 🐘 PHP project with Nostr.
  image:
    src: /assets/nostr-php_hero-splash.png
    alt: Nostr-PHP
  actions:
    - theme: brand
      text: Get started
      link: /guides/get-started
    - theme: alt
      text: Chat
      link: https://t.me/nostr_php    
    - theme: alt
      text: Source Code
      link: https://github.com/nostrver-se/nostr-php
    

features:
  - title: Publish events
    details: Create, sign and publish Nostr events to relays.
    link: /guides/publish-events
  - title: Read events
    details: Request Nostr events from relays.
    link: /guides/read-events
  - title: Examples
    details: Learn how to use this PHP helper library for Nostr.  
---

## Get started

Add the nostr-php package to your PHP project with Composer

```bash
composer require nostrverse/nostr-php
```

Here is an example how to create and publish an event to a relay:

```php
<?php

require __DIR__ . '/vendor/autoload.php';

use nostrverse\nostr\Event\Event;
use nostrverse\nostr\Key\Key;
use nostrverse\nostr\Message\EventMessage;
use nostrverse\nostr\Relay\Relay;
use nostrverse\nostr\Sign\Sign;

function send($message) {  
  try {        
        $key = new Key();
        $private_key = $key->generatePrivateKey(); // this will generate a private key
        $private_key_hex = $key->convertToHex($private_key);
        $public_key = $key->getPublicKey($private_key_hex);
        
        $relayUrl = 'wss://relay.damus.io';
        
        $note = new Event();
        $note->setKind(1);
        $note->addTag(['p', $public_key]);
        $note->addTag(['r', $relayUrl]);
        $note->setContent($message);
        
        $signer = new Sign();
        $signer->signEvent($note, $private_key);        
        
        $eventMessage = new EventMessage($note);
        
        $relay = new Relay($relayUrl);  
        $relay->setMessage($eventMessage);      
        $result = $relay->send();
        
        if ($result->isSuccess()) {
            print "The event has been sent to Nostr!\n";
        } else {
            print 'Something went wrong: ' . $result->message() . "\n";
        }
    } catch (Exception $e) {
        print 'Exception error: ' . $e->getMessage() . "\n";
    }
}

$message = 'Hello world ' . date('Y-m-d H:i:s');
send($message);

```
For more examples please check this [README](https://github.com/nostrver-se/nostr-php/blob/main/README.md).