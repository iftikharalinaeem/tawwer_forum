<?php
/**
 * @author Alexander Kim <alexander.k@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\APIv2;

use Garden\Web\Exception\NotFoundException;
use Vanilla\Site\DefaultSiteSection;
use Vanilla\Sphinx\Tests\Utils\SphinxTestTrait;

/**
 * Test the /api/v2/knowledge/search endpoint.
 */
class SphinxKnowledgeCustomPermissionsTest extends AbstractAPIv2Test {
    use SphinxTestTrait;

    /** @var string The resource route. */
    protected $baseUrl = "/knowledge-bases";

    /** @var string The name of the primary key of the resource. */
    protected $pk = "knowledgeBaseID";

    /** @var bool Whether to check if paging works or not in the index. */
    protected $testPagingOnIndex = false;

    protected static $addons = ['vanilla', 'sphinx', 'knowledge'];

    private static $content = [];

    /**
     * Create test roles based on role name list
     *
     * @param array $roles
     * @return array
     */
    protected function createRoles(array $roles): array {
        $res = [];
        foreach ($roles as $role) {
            $res[$role] = $this->api()->post(
                '/roles',
                [
                    'name' => $role,
                    'description' => 'Role '.$role,
                    'type' => 'member',
                    'deletable' => true,
                    'canSession' => true,
                    'personalInfo' => false,
                    'permissions' => [
                        [
                            'type' => 'global',
                            'permissions' => [
                                'kb.view' => true,
                                'articles.add' => true
                            ]
                        ]
                    ]
                ]
            )->getBody();
        }
        return $res;
    }

    /**
     * Create test users base ob user name list
     *
     * @param array $users
     * @return array
     */
    protected function createUsers(array $users): array {
        $res = [];
        foreach ($users as $user) {
            $res[$user] = $this->api()->post(
                '/users',
                [
                    'name' => str_replace(' ', '', $user),
                    'email' => str_replace(' ', '', $user).'@example.com',
                    'emailConfirmed' => true,
                    'password' => 'vanilla',
                    'roleID' => [self::$content['roles'][$user]['roleID'], 8]
                ]
            )->getBody();
        }
        return $res;
    }

    /**
     * Return role IDs array converted from incoming array of role test Key
     *
     * @param array $roleKeys
     * @return array
     */
    protected function getRoleIDs(array $roleKeys): array {
        $res = [];
        foreach ($roleKeys as $key) {
            $res[] = self::$content['roles'][$key]['roleID'];
        }
        return $res;
    }

    /**
     * Create test category for specific KB
     *
     * @param array $kb
     * @return array
     */
    protected function createCategory(array $kb): array {
        $record = [
            "name" => 'Test '.$kb['name'],
            "parentID" => $kb['rootCategoryID'],
            "knowledgeBaseID" => $kb['knowledgeBaseID'],
            "sortChildren" => "name",
            "sort" => 0,
        ];
        $cat = $this->api()->post('knowledge-categories', $record)->getBody();
        return $cat;
    }

    /**
     * Create test article
     *
     * @param array $kb
     * @return array
     */
    protected function createArticle(array $kb): array {
        $record = [
            "body" => "Test Lorem Ipsum.",
            "format" => "markdown",
            "knowledgeCategoryID" => $kb['patchCategory']['knowledgeCategoryID'],
            "locale" => "en",
            "name" => $kb['name']." Article",
            "sort" => 1,
        ];
        $article = $this->api()->post('/articles', $record)->getBody();
        return $article;
    }

    /**
     * Create test KB
     *
     * @param string $kbName
     * @param array $viewers
     * @param array $editors
     * @return mixed
     */
    protected function createKb(string $kbName, array $viewers = [], array $editors = []) {
        $record = $this->record($kbName);
        if (!empty($viewers) || !empty($editors)) {
            $record['hasCustomPermission'] = true;
            $record['viewers'] = $this->getRoleIDs($viewers);
            $record['editors'] = $this->getRoleIDs($editors);
        }
        $res = $this->api()->post(
            $this->baseUrl,
            $record
        )->getBody();

        $res['patchCategory'] = $this->createCategory($res);
        $res['patchArticle'] = $this->createArticle($res);
        return $res;
    }

    /**
     * Pseudo test to prepare data objects required
     */
    public function testPrepareData() {
        self::$content['roles'] = $this->createRoles(
            [
                'View All',
                'View Public',
                'View KB1',
                'Edit KB1',
                'View KB2',
                'Edit KB2'
            ]
        );

        self::$content['users'] = $this->createUsers(
            [
                'View All',
                'View Public',
                'View KB1',
                'Edit KB1',
                'View KB2',
                'Edit KB2'
            ]
        );

        self::$content['kbs']['KB1'] = $this->createKb('KB1', ['View All', 'View KB1'], ['Edit KB1']);
        self::$content['kbs']['KB2'] = $this->createKb('KB2', ['View All', 'View KB2'], ['Edit KB2']);
        self::$content['kbs']['KB3'] = $this->createKb('KB3');

        self::sphinxReindex();
        $this->assertTrue(true);
    }

    /**
     * Generate a unique URL code for a knowledge base
     *
     * @return string
     */
    public static function getUniqueUrlCode(): string {
        static $inc = 0;
        return 'kb-url-code' . $inc++;
    }

    /**
     * Grab values for inserting a new knowledge base.
     *
     * @param string $name Name of the knowledge base.
     * @return array
     */
    public function record(string $name = 'Test Knowledge Base'): array {
        $record = [
            'name' => $name,
            'description' => 'Test Knowledge Base DESCRIPTION',
            'viewType' => 'guide',
            'icon' => '',
            'bannerImage' => '',
            'sortArticles' => 'manual',
            'sourceLocale' => 'en',
            'urlCode' => self::getUniqueUrlCode(),
            'siteSectionGroup' => DefaultSiteSection::DEFAULT_SECTION_GROUP
        ];
        return $record;
    }

    /**
     * Test /knowledge/search api
     *
     * @param array $args
     * @depends testPrepareData
     * @dataProvider searchDataProvider
     */
    public function testSearch(array $args) {
        $this->api()->setUserID(self::$content['users'][$args['user']]['userID']);
        $params = ['query' => 'Lorem Ipsum'];
        $response = $this->api()->get('/knowledge/search?'.http_build_query($params));
        $getSearch = $response->getBody();
        $this->assertEquals($args['count'], count($getSearch));
        foreach ($args['kbs'] as $kbKey) {
            //$kbID = self::$content['kbs'][$kbKey]['knowledgeBaseID'];
            $found = false;
            foreach ($getSearch as $item) {
                if (substr($item['name'], 0, 3) === $kbKey) {
                    $found = true;
                    break;
                }
            }
            $this->assertTrue($found, $kbKey.'article expected but not present in search results (api response)');
        }
    }

    /**
     * Data provider for testSearch()
     */
    public function searchDataProvider() {
        return [
            'View All' => [
                [
                    'user' => 'View All',
                    'count' => 3,
                    'kbs' => ['KB1', 'KB2', 'KB3']
                ]
            ],
            'View Public' => [
                [
                    'user' => 'View Public',
                    'count' => 1,
                    'kbs' => ['KB3']
                ]
            ],
            'View KB1' => [
                [
                    'user' => 'View KB1',
                    'count' => 2,
                    'kbs' => ['KB1', 'KB3']
                ]
            ],
            'Edit KB1' => [
                [
                    'user' => 'Edit KB1',
                    'count' => 2,
                    'kbs' => ['KB1', 'KB3']
                ]
            ],
            'View KB2' => [
                [
                    'user' => 'View KB2',
                    'count' => 2,
                    'kbs' => ['KB2', 'KB3']
                ]
            ],
            'Edit KB2' => [
                [
                    'user' => 'Edit KB2',
                    'count' => 2,
                    'kbs' => ['KB2', 'KB3']
                ]
            ],
        ];
    }
}
