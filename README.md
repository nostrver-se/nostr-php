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

TODO
