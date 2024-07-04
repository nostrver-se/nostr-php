<?php

declare(strict_types=1);

namespace swentel\nostr\Application;

use swentel\nostr\Event\Event;
use swentel\nostr\Message\EventMessage;
use swentel\nostr\Relay\Relay;
use swentel\nostr\Sign\Sign;

class Client
{
    /**
     * Run the Nostr Client.
     *
     * @param $args
     */
    public function run($args): void
    {

        // This is a very basic start, we look for 6 keys in the incoming
        // args which have to be, --content, --key, --relay. If found, we
        // will send a text note to this relay.

        $content = $socket = $private_key_file = '';

        if (count($args) !== 7) {
            $this->showHelp('Missing arguments');
            return;
        }

        foreach ($args as $current_key => $value) {
            switch ($value) {
                case '--content':
                    $array_key = $current_key + 1;
                    $content = trim($args[$array_key]);
                    break;
                case '--relay':
                    $array_key = $current_key + 1;
                    $socket = trim($args[$array_key]);
                    break;
                case '--key':
                    $array_key = $current_key + 1;
                    $private_key_file = trim($args[$array_key]);
                    break;
            }
        }

        if (empty($content)) {
            $this->showHelp('The content is empty');
            return;
        }

        if (empty($socket)) {
            $this->showHelp('The relay is empty');
            return;
        }

        if (empty($private_key_file)) {
            $this->showHelp('The path to the private key is empty');
            return;
        }

        if (!file_exists($private_key_file)) {
            $this->showHelp('The private key file does not exist');
            return;
        }

        $private_key = trim(file_get_contents($private_key_file));
        if (empty($private_key)) {
            $this->showHelp('The private key file is empty');
            return;
        }

        // ------------------------------------
        // Basic validation done.

        $event = new Event();
        $event->setContent($content)->setKind(1);
        $signer = new Sign();
        $signer->signEvent($event, $private_key);
        $eventMessage = new EventMessage($event);
        $relay = new Relay($socket, $eventMessage);
        $result = $relay->send();
        if ($result->isSuccess()) {
            print "Send to Nostr!\n";
        } else {
            print "Something went wrong: " . $result->message() . "\n";
        }
    }

    protected function showHelp($message): void
    {
        print "\n[error] " . $message . "\n\nUsage:\n";
        print "bin/nostr-php --content \"Hello world!\" --key /home/path/to/nostr-private.key --relay wss://nostr.pleb.network\n\n";
    }
}
