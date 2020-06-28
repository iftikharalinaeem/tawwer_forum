<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\Knowledge\APIv2\Articles;

/**
 * Test translating community data into a format that can easily be used to create knowledge base data.
 */
class ArticleFromDiscussionTest extends AbstractAPIv2Test {

    /**
     * This method is called before the first test of this test class is run.
     */
    public static function setupBeforeClass(): void {
        self::$addons = ["vanilla", "knowledge"];
        parent::setupBeforeClass();
    }

    /**
     * Test getting discussion data in a format that is easy-to-consume when creating an article.
     */
    public function testArticleFromDiscussion() {
        $post = [
            "body" => "Hello world.",
            "categoryID" => 1,
            "format" => "markdown",
            "name" => __FUNCTION__,
        ];
        $discussion = $this->api()->post("discussions", $post)->getBody();
        $articleFromDiscussion = $this->api()->get("articles/from-discussion", ["discussionID" => $discussion["discussionID"]]);

        $this->assertEquals(200, $articleFromDiscussion->getStatusCode());

        $result = $articleFromDiscussion->getBody();
        $this->assertEquals($post["format"], $result["format"]);
        $this->assertEquals($discussion["body"], $result["body"]);
        $this->assertEquals($discussion["url"], $result["url"]);
    }

    /**
     * Test getting rich discussion data in a format that is easy-to-consume when creating an article.
     */
    public function testArticleFromRichDiscussion() {
        $post = [
            "body" => '[{"insert":"Hello world.\n"}]',
            "categoryID" => 1,
            "format" => "rich",
            "name" => __FUNCTION__,
        ];
        $discussion = $this->api()->post("discussions", $post)->getBody();
        $articleFromDiscussion = $this->api()->get("articles/from-discussion", ["discussionID" => $discussion["discussionID"]]);

        $this->assertEquals(200, $articleFromDiscussion->getStatusCode());

        $result = $articleFromDiscussion->getBody();
        $this->assertEquals($post["format"], $result["format"]);
        $this->assertEquals(json_decode($post["body"], true), $result["body"]);
        $this->assertEquals($discussion["url"], $result["url"]);
    }
}
