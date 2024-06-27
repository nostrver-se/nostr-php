---
title: Generate keys
---

# Generate keys

## Create a private key

```php
$privateKey = new Key();
$privateKey->generatePrivateKey();
```

## Get public key from a private key

```php
$key = new Key();
$private_key_hex = $privateKey->convertToHex($private_key);
$public_key = $key->getPublicKey($private_key_hex);
```
