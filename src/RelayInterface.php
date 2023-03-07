<?php

namespace swentel\nostr;

/**
 *
 */
interface RelayInterface {
  public function publish($event);
}
