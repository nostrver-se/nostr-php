<?php

declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';

use swentel\nostr\Event\Event;

$json = '{"kind":27236,"created_at":1707827688371,"tags":[],"content":"","pubkey":"07adfda9c5adc80881bb2a5220f6e3181e0c043b90fa115c4f183464022968e6","id":"7eaf0e97515c7ea8846fa2b1e28a480bec506a3f911a1ec998662201f986b0bf","sig":"22a99a1e60266c89720bf0af46ec60132eff17782aa7f582c6f990c25ef54bcefb79fdd8a95ca8bbdab9e96a0d1fd85b77a6c37e192bf74b77dd013a1d539028"}';

$event = new Event();
$isValid = $event->verify($json);

print $isValid;
