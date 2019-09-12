<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

use Garden\Schema\Schema;
use VanillaTests\APIv2\AbstractAPIv2Test;

class SphinxSearchTest extends AbstractAPIv2Test {

    /** @var array */
    protected static $category;

    /** @var array */
    protected static $discussion;

   /** @var array */
   protected static $discussionAugust;

    /** @var array */
    protected static $comment;

    /** @var Schema */
    protected static $searchResultSchema;

    protected static $addons = ['vanilla', 'sphinx', 'advancedsearch'];

    /**
     * {@inheritdoc}
     */
    public static function setupBeforeClass() {
        parent::setupBeforeClass();
       //exec('curl 127.0.0.1:9399', $dockerResponse);
       // die(print_r($dockerResponse));

        /** @var \Gdn_Session $session */
        $session = self::container()->get(\Gdn_Session::class);
        $session->start(self::$siteInfo['adminUserID'], false, false);

        /** @var \CategoriesApiController $categoriesAPIController */
        $categoriesAPIController = static::container()->get('CategoriesApiController');
        /** @var \DiscussionsApiController $discussionsAPIController */
        $discussionsAPIController = static::container()->get('DiscussionsApiController');
        /** @var \CommentsApiController $commentAPIController */
        $commentAPIController = static::container()->get('CommentsApiController');

        $tmp = uniqid('category_');
        self::$category = $categoriesAPIController->post([
            'name' => $tmp,
            'urlcode' => $tmp,
        ]);

        $tmp = uniqid('discussion_');
        self::$discussion = $discussionsAPIController->post([
            'name' => $tmp,
            'body' => $tmp,
            'format' => 'markdown',
            'categoryID' => self::$category['categoryID'],
        ]);
        self::$discussion['rawBody'] = $tmp;

       self::$discussionAugust = $discussionsAPIController->post([
          'name' => 'August test 2019',
          'body' => 'August test 2019',
          'format' => 'markdown',
          'categoryID' => self::$category['categoryID'],
       ]);

        $tmp = uniqid('comment_');
        self::$comment = $commentAPIController->post([
            'body' => $tmp,
            'format' => 'markdown',
            'discussionID' => self::$discussion['discussionID'],
        ]);
        self::$comment['rawBody'] = $tmp;

       self::SphinxReindex();

        /** @var SearchApiController $searchAPIController */
        $searchAPIController = static::container()->get('SearchApiController');
        self::$searchResultSchema = $searchAPIController->fullSchema();

        $session->end();
    }

    public static function sphinxReindex() {
//       $sphinxHost = c('Plugins.Sphinx.Server', 'localhost');
//       if ($sphinxHost === 'localhost') {
//          exec('indexer --all --rotate', $dockerResponse);
//       } else {
//          // this is for localhost with sphinx on docker
         $continue = 3;
         while ($continue) {
            exec('curl 127.0.0.1:9399', $dockerResponse);
            if ('Sphinx reindexed.' !== end($dockerResponse)) {
               //die('Can\'t reindex Sphinx indexes!'."\n".end($dockerResponse));
               sleep(3);
               $continue--;
            } else {
               $continue = false;
            }
         }

//       }
    }

    /**
     * Test search scoped to discussions.
     */
    public function testRecordTypesDiscussion() {
        $params = [
            'query' => self::$discussion['name'],
           //'query' => 'august',
            'recordTypes' => 'discussion',
        ];
        $response = $this->api()->get('/search?'.http_build_query($params));
        $this->assertEquals(200, $response->getStatusCode());

        $results = $response->getBody();

        $this->assertTrue(count($results) === 1);
        foreach ($results as $result) {
            self::$searchResultSchema->validate($result);
        }
        $this->assertRowsEqual(['recordType' => 'discussion'], $results[0]);
    }

    /**
     * Test search scoped to comments.
     */
    public function testRecordTypesComment() {
        $params = [
            'query' => self::$comment['rawBody'],
            'recordTypes' => 'comment',
        ];
        $response = $this->api()->get('/search?'.http_build_query($params));
        $this->assertEquals(200, $response->getStatusCode());

        $results = $response->getBody();

        $this->assertEquals(1, count($results));
        foreach ($results as $result) {
            self::$searchResultSchema->validate($result);
        }
        $this->assertRowsEqual(['recordType' => 'comment'], $results[0]);
    }

    /**
     * Test search scoped to a discussion.
     */
    public function testExistingDiscussionID() {
        $params = [
            'query' => self::$comment['rawBody'],
            'discussionID' => self::$discussion['discussionID'],
        ];
        $response = $this->api()->get('/search?'.http_build_query($params));
        $this->assertEquals(200, $response->getStatusCode());

        $results = $response->getBody();

        $this->assertEquals(1, count($results));
        foreach ($results as $result) {
            self::$searchResultSchema->validate($result);
        }
        $this->assertRowsEqual(['recordType' => 'comment'], $results[0]);
    }

    /**
     * Test search scoped to a non existing discussion.
     */
    public function testNonExistingDiscussionID() {
        $params = [
            'query' => self::$comment['rawBody'],
            'discussionID' => 999999,
        ];
        $response = $this->api()->get('/search?'.http_build_query($params));
        $this->assertEquals(200, $response->getStatusCode());

        $results = $response->getBody();

        $this->assertTrue(count($results) === 0);
    }

    /**
     * Test search scoped to a category.
     */
    public function testExistingCategoryID() {
        $params = [
            'query' => self::$discussion['name'],
            'categoryID' => self::$category['categoryID'],
        ];
        $response = $this->api()->get('/search?'.http_build_query($params));

        $this->assertEquals(200, $response->getStatusCode());

        $results = $response->getBody();

        $this->assertTrue(count($results) === 1);
        foreach ($results as $result) {
            self::$searchResultSchema->validate($result);
        }
    }

    /**
     * Test search scoped to a non existing category.
     */
    public function testNonExistingCategoryID() {
        $params = [
            'query' => self::$discussion['name'],
            'categoryID' => 777,
        ];
       //die(var_dump($this->api()));
       $api = $this->api();
        $response = $api->get('/search?'.http_build_query($params));

        $this->assertEquals(200, $response->getStatusCode());

        $results = $response->getBody();
        $this->assertEquals(0, count($results));
    }



    /**
     * Test search by user names.
     */
    public function testInsertUserNames() {
        $params = [
            'query' => self::$discussion['name'],
            'insertUserNames' => 'travis,daffewfega',
        ];
        $response = $this->api()->get('/search?'.http_build_query($params));
        $this->assertEquals(200, $response->getStatusCode());

        $results = $response->getBody();

        $this->assertTrue(count($results) === 1);
        foreach ($results as $result) {
            self::$searchResultSchema->validate($result);
        }
    }

    /**
     * Test search by user IDs
     */
    public function testInsertUserIDs() {
        $params = [
            'query' => self::$discussion['name'],
            'insertUserIDs' => '1,2',
        ];
        $response = $this->api()->get('/search?'.http_build_query($params));
        $this->assertEquals(200, $response->getStatusCode());

        $results = $response->getBody();

        $this->assertTrue(count($results) === 1);
        foreach ($results as $result) {
            self::$searchResultSchema->validate($result);
        }
    }

}
