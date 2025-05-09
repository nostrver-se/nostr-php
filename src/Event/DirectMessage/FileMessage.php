<?php

declare(strict_types=1);

namespace swentel\nostr\Event\DirectMessage;

use swentel\nostr\Event\Event;

class FileMessage extends Event
{
    /**
     * Construct a File Message event (kind 15)
     */
    public function __construct()
    {
        parent::__construct();
        $this->setKind(15);
    }

    /**
     * Add a recipient to the file message
     *
     * @param string $pubkey The recipient's public key
     * @param string|null $relayUrl Optional relay URL for the recipient
     * @return self
     */
    public function addRecipient(string $pubkey, ?string $relayUrl = null): self
    {
        $tag = ['p', $pubkey];
        if ($relayUrl !== null) {
            $tag[] = $relayUrl;
        }

        $this->addTag($tag);
        return $this;
    }

    /**
     * Set the file type
     *
     * @param string $mimeType The file MIME type
     * @return self
     */
    public function setFileType(string $mimeType): self
    {
        $this->addTag(['file-type', $mimeType]);
        return $this;
    }

    /**
     * Set the encryption algorithm
     *
     * @param string $algorithm The encryption algorithm used
     * @return self
     */
    public function setEncryptionAlgorithm(string $algorithm): self
    {
        $this->addTag(['encryption-algorithm', $algorithm]);
        return $this;
    }

    /**
     * Set the decryption key
     *
     * @param string $key The decryption key
     * @return self
     */
    public function setDecryptionKey(string $key): self
    {
        $this->addTag(['decryption-key', $key]);
        return $this;
    }

    /**
     * Set the decryption nonce
     *
     * @param string $nonce The decryption nonce
     * @return self
     */
    public function setDecryptionNonce(string $nonce): self
    {
        $this->addTag(['decryption-nonce', $nonce]);
        return $this;
    }

    /**
     * Set the file hash
     *
     * @param string $hash The SHA-256 hexencoded string of the file
     * @return self
     */
    public function setFileHash(string $hash): self
    {
        $this->addTag(['x', $hash]);
        return $this;
    }

    /**
     * Set the file URL in the content
     *
     * @param string $url URL where the file can be downloaded
     * @return self
     */
    public function setFileUrl(string $url): self
    {
        $this->setContent($url);
        return $this;
    }
}
