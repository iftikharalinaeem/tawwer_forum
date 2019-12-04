<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Ranks\Tests\Models;

use PHPUnit\Framework\TestCase;
use VanillaTests\SiteTestTrait;

/**
 * Tests for the `RankModel` class.
 */
class RankModelTest extends TestCase {
    use SiteTestTrait;

    /**
     * @var \RankModel
     */
    private $rankModel;

    /**
     * {@inheritDoc}
     */
    public static function getAddons(): array {
        return ['vanilla', 'ranks'];
    }

    /**
     * Setup
     */
    public function setUp(): void {
        parent::setUp();
        $this->rankModel = $this->container()->get(\RankModel::class);
    }

    /**
     * Test sample HTML that should have external links.
     *
     * @param string $html
     * @dataProvider provideHtmlWithExternalLinks
     */
    public function testHtmlWithExternalLinks(string $html): void {
        $actual = $this->rankModel->hasExternalLinks($html);
        $this->assertTrue($actual);
    }

    /**
     * Test sample HTML that should NOT have external links.
     *
     * @param string $html
     * @dataProvider provideHtmlWithoutExternalLinks
     */
    public function testHtmlWithoutExternalLinks(string $html): void {
        $actual = $this->rankModel->hasExternalLinks($html);
        $this->assertFalse($actual);
    }

    /**
     * Test URLs that should be internal.
     *
     * @param string $url
     * @dataProvider provideInternalUrls
     */
    public function testNotExternalHost(string $url): void {
        $actual = $this->rankModel->isExternalHost($url, 'vanilla.test');
        $this->assertFalse($actual);
    }

    /**
     * A different host is different.
     */
    public function testExternalHost(): void {
        $this->assertTrue($this->rankModel->isExternalHost('http://example.com', 'vanilla.test'));
    }

    /**
     * @return array
     */
    public function provideInternalUrls(): array {
        $r = [
            'empty' => [''],
            'slash' => ['/'],
            'path' => ['/path'],
            'current' => ['http://vanilla.test/'],
            'cdn' => ['http://foo.v-cdn.net/bar'],
        ];
        return $r;
    }

    /**
     * Provide sample HTML with external links.
     *
     * @return array
     */
    public function provideHtmlWithExternalLinks(): array {
        $r = [
            'basic' => ['<a href="https://example.com">e</a>'],
            'mixed' => ['<a href="/foo">a</a><a href="http://example.com">b</a>'],
            'entry/leaving' => ['<a href="/home/leaving?target=http%3A%2F%2Fgoogle.com" class="Popup" rel="nofollow">google.com</a>'],
        ];

        return $r;
    }

    /**
     * Provide sample HTML without external links.
     *
     * @return array
     */
    public function provideHtmlWithoutExternalLinks(): array {
        $r = [
            'basic' => ['Hello <b>world</b>'],
            'quote' => [<<<EOT
<blockquote class="Quote UserQuote blockquote">
<div class="blockquote-content">
    <a rel="nofollow" href="/profile/Frank">Frank</a>
    wrote: <a rel="nofollow" href="/discussion/comment/370536#Comment_370536" class="QuoteLink">»</a></div>
<div class="blockquote-content">Okay wut!?</div>
</blockquote>
EOT
            ],
            'rich image upload' => [<<<EOT
<div class="embedExternal embedImage">
    <div class="embedExternal-content">
        <a class="embedImage-link" href="https://us.v-cdn.net/123/uploads/086/JFHHT340P1LA.jpg" rel="nofollow noreferrer noopener ugc" target="_blank">
            <img class="embedImage-img" src="https://us.v-cdn.net/123/uploads/086/JFHHT340P1LA.jpg" alt="success.jpg">
        </a>
    </div>
</div>
EOT
            ],
        ];

        return $r;
    }
}
