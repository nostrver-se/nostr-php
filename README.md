# nostr-php

PHP Helper library for Nostr.

## Signing an event

Calculates the id and signs an event. Both properties are added to the array.

```
$event = [
  'pubkey' => $public_key,
  'created_at' => time(),
  'kind' => 1,
  'tags' => [],
  'content' => trim($entity->get('body')->value),
];
$signer = new Sign();
$event = $signer->sign($event);
```

## Converting keys

TODO