<?php

declare(strict_types=1);

namespace swentel\nostr\Application;

use swentel\nostr\Event\Event;
use swentel\nostr\Message\EventMessage;
use swentel\nostr\Relay\Relay;
use swentel\nostr\Sign\Sign;
use Garden\Cli\Cli;

class Client
{
    /**
     * Run the Nostr Client.
     *
     * @param $args
     */
    public function run($args): void
    {

        // This is a very basic start, we look for several arguments in the incoming
        // args which minimum have to be, --content, --key, --relay. If found, we
        // will send a text note to this relay.

        $content = $relay = $private_key_file = '';

        // Define the cli options.
        $cli = new Cli();

        $cli->description('Send nostr events to relays')
            ->opt('content:c', 'Content (Required)')
            ->opt('key:p', 'Private key file location to use (Required).')
            ->opt('relay:r', 'Relays to publish the events (Required).')
            ->opt('kind:k', 'Event kinds (Optional, Default: 1).', false, 'integer')
            ->opt('created-at:a', 'Event created_at in unixtime (Optional).', false, 'integer')
            ->opt('tags:t', 'Comma separated tags (Optional). Example: t:nostr,t:travel,e:eventId', false);

        // Parse and return cli args.
        $parsedArgs = $cli->parse($args, true);

        $kind = $parsedArgs->getOpt('kind', 1);
        $createdAt = $parsedArgs->getOpt('created_at');
        $content = $parsedArgs->getOpt('content');
        $relay = $parsedArgs->getOpt('relay');
        $private_key_file = $parsedArgs->getOpt('key', '');
        $stringTags = $parsedArgs->getOpt('tags', '');
        $tags = self::parseNostrTags($stringTags);

        if (empty($content)) {
            $this->showHelp('The content is empty');
            return;
        }

        if (empty($relay)) {
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
        $event->setContent($content)->setKind($kind)->setTags($tags);

        if (isset($createdAt)) $event->setCreatedAt($createdAt);

        $signer = new Sign();
        $signer->signEvent($event, $private_key);
        $eventMessage = new EventMessage($event);
        $relay = new Relay($relay, $eventMessage);
        $result = $relay->send();
        if ($result->isSuccess()) {
            print "Send to Nostr!\n";
        } else {
            print "Something went wrong: " . $result->message() . "\n";
        }
    }

    protected function showHelp($message): void
    {
        print "\n[error] " . $message . "\n\nUsage:\n\n";
        print "To get complete help for commands:\n\nnostr-php --help\n\n";
        print "Basic Usage:\n\nnostr-php --content \"Hello world!\" --key /home/path/to/nostr-private.key --relay wss://nostr.pleb.network\n\n";
    }

    /**
     * parseNostrTags is a helper function for the CLI that parses a string of tags and returns an array of tag-value pairs.
     *
     * Example: If the input string is "t:nostr,t:food,e:eventId,p:pubkey", it will be parsed as [["t","nostr"], ["t","food"], ["e","eventId"], ["p","pubkey"]].
     *
     * @param string $stringTags A string of tags separated by commas.
     * @return array An array containing the parsed tag-value pairs.
     **/
    public static function parseNostrTags(string $stringTags = ''): array
    {
        // Split the string of tags into an array using ',' as the delimiter
        $tempTags = explode(',', trim($stringTags));

        // Check if the $tempTags array is empty or false
        if (empty($tempTags)) {
            return [];
        }

        $result = [];
        foreach ($tempTags as $value) {
            // Split each tag-value pair into an array using ':' as the delimiter
            $innerValue = explode(':', trim($value));

            // Check if the $innerValue array is false or has less than 2 elements
            if ($innerValue === false || count($innerValue) < 2) {
                continue;
            }

            // Add the tag-value pair to the result array
            $result[] = $innerValue;
        }

        // Return the final array of parsed tag-value pairs
        return $result;
    }
}
