<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\APIv2;

use Garden\Http\HttpResponse;
use Vanilla\Formatting\Formats\HtmlFormat;
use Vanilla\Formatting\Formats\TextFormat;
use Vanilla\Knowledge\Controllers\Api\ArticlesApiHelper;
use VanillaTests\Knowledge\Utils\KbApiTestCase;

/**
 * Tests for article file rehosting.
 */
class ArticleFileRehostTest extends KbApiTestCase {

    /**
     * Test rehosting of articles.
     */
    public function testPostRehost() {
        $fakeOwnAsset = asset('/uploads/somefile.png', true);
        $kb = $this->createKnowledgeBase();
        $response = $this->api()->post('/articles', [
            'name' => 'Hello world',
            'locale' => 'en',
            'knowledgeCategoryID' => $kb['rootCategoryID'],
            'body' => "<img src='https://vanillaforums.com/svgs/logo.svg' /><img src='$fakeOwnAsset' />",
            'format' => HtmlFormat::FORMAT_KEY,
            'fileRehosting' => [
                'enabled' => true,
            ],
        ]);

        $this->assertRehosted($response);
    }

    /**
     * Test rehosting of articles.
     */
    public function testPatchRehost() {
        $fakeOwnAsset = asset('/uploads/somefile.png', true);
        $this->createKnowledgeBase();
        $article = $this->createArticle();
        $articleID = $article['articleID'];
        $response = $this->api()->patch("/articles/$articleID", [
            'body' => "<img src='https://vanillaforums.com/svgs/logo.svg' /><img src='$fakeOwnAsset' />",
            'format' => HtmlFormat::FORMAT_KEY,
            'fileRehosting' => [
                'enabled' => true,
            ],
        ]);

        $this->assertRehosted($response);
    }

    /**
     * Assert that files were properly rehosted.
     *
     * @param HttpResponse $response
     */
    private function assertRehosted(HttpResponse $response) {
        $body = $response->getBody()['body'];
        $imageUrls = \Gdn::formatService()->parseImageUrls($body, HtmlFormat::FORMAT_KEY);
        $this->assertCount(2, $imageUrls);
        $this->assertStringMatchesFormat(asset('/uploads/migrated/%s/logo.svg', true), $imageUrls[0]);

        $this->assertEquals(1, $response->getHeader(ArticlesApiHelper::REHOST_SUCCESS_HEADER));
        $this->assertEquals(0, $response->getHeader(ArticlesApiHelper::REHOST_FAILED_HEADER));
    }
}
