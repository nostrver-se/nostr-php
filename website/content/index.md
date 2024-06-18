---
# https://vitepress.dev/reference/default-theme-home-page
layout: home

hero:
  name: "nostr-php"
  text: "A PHP helper library for Nostr"
#  tagline: My great project tagline
  actions:
#    - theme: brand
#      text: Markdown Examples
#      link: /markdown-examples
    - theme: alt
      text: Chat
      link: https://t.me/nostr_php    
    - theme: alt
      text: View source code
      link: https://github.com/nostrver-se/nostr-php
    

features:
  - title: Publish events
    details: Lorem ipsum dolor sit amet, consectetur adipiscing elit
  - title: Read events
    details: Lorem ipsum dolor sit amet, consectetur adipiscing elit
  - title: Examples
    details: Lorem ipsum dolor sit amet, consectetur adipiscing elit  
---

Add the nostr-php to your PHP project with Composer

```bash
composer require nostrverse/nostr-php
```

Here is very simple example:

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
        $relay = new Relay($relayUrl, $eventMessage);        
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