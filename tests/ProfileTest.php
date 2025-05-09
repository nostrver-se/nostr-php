<?php

declare(strict_types=1);

namespace swentel\nostr\Tests;

use PHPUnit\Framework\TestCase;
use swentel\nostr\Event\Profile\Profile;

/**
 * Tests for the Profile class (kind 0 events).
 */
class ProfileTest extends TestCase
{
    /**
     * Test a completely empty profile.
     */
    public function testEmptyProfile(): void
    {
        $profile = new Profile();
        $profile->setContent('');

        $content = $profile->getContent();
        $contentArray = json_decode($content, true);

        $this->assertIsArray($contentArray);
        $this->assertEmpty($contentArray);
    }

    /**
     * Test a partially filled profile.
     */
    public function testPartiallyFilledProfile(): void
    {
        $profile = new Profile();
        $profile->setName('John Doe');
        $profile->setPicture('https://example.com/avatar.jpg');
        // Intentionally not setting about and other fields
        $profile->setContent('');

        $content = $profile->getContent();
        $contentArray = json_decode($content, true);

        $this->assertArrayHasKey('name', $contentArray);
        $this->assertArrayHasKey('picture', $contentArray);
        $this->assertArrayNotHasKey('about', $contentArray);
        $this->assertArrayNotHasKey('nip05', $contentArray);

        $this->assertEquals('John Doe', $contentArray['name']);
        $this->assertEquals('https://example.com/avatar.jpg', $contentArray['picture']);
    }

    /**
     * Test setting all available profile fields.
     */
    public function testFullyFilledProfile(): void
    {
        $profile = new Profile();
        $profile->setName('John Doe');
        $profile->setAbout('I am a developer');
        $profile->setPicture('https://example.com/avatar.jpg');
        $profile->setDisplayName('Johnny');
        $profile->setNip05('john@example.com');
        $profile->setBanner('https://example.com/banner.jpg');
        $profile->setWebsite('https://example.com');
        $profile->setLud06('lnurl1dp68gurn8ghj7um9wfmxjcm99e3k7mf0v9cxj0m385ekvcenxc6r2c35xvukxefcv5mkvv34x5ekzd3ev56nyd3hxqurzepexejxxepnxscrvwfnv9nxzcn9xq6xyefhvgcxxcmyxymnserxfq5fns');
        $profile->setLud16('john@example.com');
        $profile->setContent('');

        $content = $profile->getContent();
        $contentArray = json_decode($content, true);

        $this->assertCount(9, $contentArray);
        $this->assertEquals('John Doe', $contentArray['name']);
        $this->assertEquals('I am a developer', $contentArray['about']);
        $this->assertEquals('https://example.com/avatar.jpg', $contentArray['picture']);
        $this->assertEquals('Johnny', $contentArray['display_name']);
        $this->assertEquals('john@example.com', $contentArray['nip05']);
        $this->assertEquals('https://example.com/banner.jpg', $contentArray['banner']);
        $this->assertEquals('https://example.com', $contentArray['website']);
        $this->assertEquals('lnurl1dp68gurn8ghj7um9wfmxjcm99e3k7mf0v9cxj0m385ekvcenxc6r2c35xvukxefcv5mkvv34x5ekzd3ev56nyd3hxqurzepexejxxepnxscrvwfnv9nxzcn9xq6xyefhvgcxxcmyxymnserxfq5fns', $contentArray['lud06']);
        $this->assertEquals('john@example.com', $contentArray['lud16']);
    }

    /**
     * Test parsing a profile with all fields from JSON content.
     */
    public function testParseProfileAllFields(): void
    {
        $jsonContent = json_encode([
            'name' => 'John Doe',
            'about' => 'I am a developer',
            'picture' => 'https://example.com/avatar.jpg',
            'display_name' => 'Johnny',
            'nip05' => 'john@example.com',
            'banner' => 'https://example.com/banner.jpg',
            'website' => 'https://example.com',
            'lud06' => 'lnurl1dp68gurn8ghj7um9wfmxjcm99e3k7mf0v9cxj0m385ekvcenxc6r2c35xvukxefcv5mkvv34x5ekzd3ev56nyd3hxqurzepexejxxepnxscrvwfnv9nxzcn9xq6xyefhvgcxxcmyxymnserxfq5fns',
            'lud16' => 'john@example.com',
        ]);

        $profile = new Profile();
        $profile->parseProfile($jsonContent);

        $this->assertEquals('John Doe', $profile->name);
        $this->assertEquals('I am a developer', $profile->about);
        $this->assertEquals('https://example.com/avatar.jpg', $profile->picture);
        $this->assertEquals('Johnny', $profile->display_name);
        $this->assertEquals('john@example.com', $profile->nip05);
        $this->assertEquals('https://example.com/banner.jpg', $profile->banner);
        $this->assertEquals('https://example.com', $profile->website);
        $this->assertEquals('lnurl1dp68gurn8ghj7um9wfmxjcm99e3k7mf0v9cxj0m385ekvcenxc6r2c35xvukxefcv5mkvv34x5ekzd3ev56nyd3hxqurzepexejxxepnxscrvwfnv9nxzcn9xq6xyefhvgcxxcmyxymnserxfq5fns', $profile->lud06);
        $this->assertEquals('john@example.com', $profile->lud16);
    }

    /**
     * Test parsing a partially filled profile from JSON content.
     */
    public function testParseProfilePartialFields(): void
    {
        $jsonContent = json_encode([
            'name' => 'John Doe',
            'picture' => 'https://example.com/avatar.jpg',
        ]);

        $profile = new Profile();
        $profile->parseProfile($jsonContent);

        $this->assertEquals('John Doe', $profile->name);
        $this->assertEquals('https://example.com/avatar.jpg', $profile->picture);
        $this->assertNull($profile->about);
        $this->assertNull($profile->display_name);
        $this->assertNull($profile->nip05);
        $this->assertNull($profile->banner);
        $this->assertNull($profile->website);
        $this->assertNull($profile->lud06);
        $this->assertNull($profile->lud16);
    }

    /**
     * Test parsing an empty JSON profile.
     */
    public function testParseEmptyProfile(): void
    {
        $jsonContent = '{}';

        $profile = new Profile();
        $profile->parseProfile($jsonContent);

        $this->assertNull($profile->name);
        $this->assertNull($profile->about);
        $this->assertNull($profile->picture);
        $this->assertNull($profile->display_name);
        $this->assertNull($profile->nip05);
        $this->assertNull($profile->banner);
        $this->assertNull($profile->website);
        $this->assertNull($profile->lud06);
        $this->assertNull($profile->lud16);
    }

    /**
     * Test handling deprecated properties (username instead of name).
     */
    public function testDeprecatedProperties(): void
    {
        $jsonContent = json_encode([
            'username' => 'johndoe',
            'about' => 'I am a developer',
            'picture' => 'https://example.com/avatar.jpg',
        ]);

        $profile = new Profile();
        $profile->parseProfile($jsonContent);

        $this->assertEquals('johndoe', $profile->name);
        $this->assertEquals('I am a developer', $profile->about);
        $this->assertEquals('https://example.com/avatar.jpg', $profile->picture);
    }

    /**
     * Test invalid JSON content.
     */
    public function testInvalidJsonContent(): void
    {
        $this->expectException(\JsonException::class);

        $invalidJson = '{invalid json}';

        $profile = new Profile();
        $profile->parseProfile($invalidJson);
    }

    /**
     * Test with missing required properties but valid JSON.
     */
    public function testMissingProperties(): void
    {
        $jsonContent = json_encode([
            'other_field' => 'some value',
            'another_field' => 123,
        ]);

        $profile = new Profile();
        $profile->parseProfile($jsonContent);

        $this->assertNull($profile->name);
        $this->assertNull($profile->about);
        $this->assertNull($profile->picture);
    }
}
