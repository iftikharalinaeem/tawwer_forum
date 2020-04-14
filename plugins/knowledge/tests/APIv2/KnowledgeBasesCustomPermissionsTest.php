<?php
/**
 * @author Alexander Kim <alexander.k@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\APIv2;

use Garden\Web\Exception\NotFoundException;
use Garden\Web\Exception\ForbiddenException;
use Vanilla\Site\DefaultSiteSection;
use Vanilla\Exception\PermissionException;

/**
 * Test the /api/v2/ knowledge endpoints.
 */
class KnowledgeBasesCustomPermissionsTest extends AbstractAPIv2Test {

    const GUEST_ROLE_ID = 2;

    /** @var string The resource route. */
    protected $baseUrl = "/knowledge-bases";

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
                        ],
                        [
                            'type' => 'global',
                            'permissions' => [
                                'signIn.allow' => true,
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
            if (is_array($user)) {
                $roleKeys = reset($user);
                $userKey = key($user);
            } else {
                $userKey = $user;
            }
            if (is_string($user)) {
                $roles = [self::$content['roles'][$user]['roleID']];
            } else {
                $roles= [];
                foreach ($roleKeys as $roleKey) {
                    $roles[] = is_string($roleKey) ? self::$content['roles'][$roleKey]['roleID'] : $roleKey;
                }
            }
            $res[$userKey] = $this->api()->post(
                '/users',
                [
                    'name' => str_replace(' ', '', $userKey),
                    'email' => str_replace(' ', '', $userKey).'@example.com',
                    'emailConfirmed' => true,
                    'password' => 'vanilla',
                    'roleID' => $roles
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
            $record['viewRoleIDs'] = $this->getRoleIDs($viewers);
            $record['editRoleIDs'] = $this->getRoleIDs($editors);
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
                'Edit KB2',
            ]
        );
        self::$content['roles']['Nothing'] = $this->api()->post(
            '/roles',
            [
                'name' => 'Nothing',
                'description' => 'Role Nothing',
                'type' => 'member',
                'deletable' => true,
                'canSession' => true,
                'personalInfo' => false,
                'permissions' => [
                    [
                        'type' => 'global',
                        'permissions' => [
                            'kb.view' => false,
                            'articles.add' => false
                        ]
                    ]
                ]
            ]
        )->getBody();

        self::$content['users'] = $this->createUsers(
            [
                'Nothing',
                'View All',
                'View Public',
                'View KB1',
                'Edit KB1',
                'View KB2',
                'Edit KB2',
                ['View All Member' => [8, 'Edit KB2', 'View All']]
            ]
        );

        self::$content['kbs']['KB1'] = $this->createKb('KB1', ['View All', 'View KB1'], ['Edit KB1']);
        self::$content['kbs']['KB2'] = $this->createKb('KB2', ['View All', 'View KB2'], ['Edit KB2']);
        self::$content['kbs']['KB3'] = $this->createKb('KB3');

        $this->assertTrue(true);
    }

    /**
     * Data provider for testIndex()
     */
    public function indexDataProvider() {
        return [
            'Nothing' => [
                [
                    'user' => 'Nothing',
                    'count' => ForbiddenException::class,
                    'kbs' => []
                ]
            ],
            'View All' => [
                [
                    'user' => 'View All',
                    'count' => 3,
                    'kbs' => ['KB1', 'KB2', 'KB3']
                ]
            ],
            'View All Member' => [
                [
                    'user' => 'View All Member',
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
            'View KB2' => [
                [
                    'user' => 'View KB2',
                    'count' => 2,
                    'kbs' => ['KB2', 'KB3']
                ]
            ],
        ];
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
     * Test /knowledge-bases
     *
     * @param array $args
     * @depends testPrepareData
     * @dataProvider indexDataProvider
     */
    public function testIndex(array $args) {
        $this->api()->setUserID(self::$content['users'][$args['user']]['userID']);
        if (is_string($args['count'])) {
            $this->expectException($args['count']);
            $getIndex = $this->api()->get($this->baseUrl)->getBody();
        } else {
            $getIndex = $this->api()->get($this->baseUrl)->getBody();
            $this->assertEquals($args['count'], count($getIndex));
            foreach ($args['kbs'] as $kbKey) {
                $kbID = self::$content['kbs'][$kbKey]['knowledgeBaseID'];
                $found = false;
                foreach ($getIndex as $kb) {
                    if ($kb['knowledgeBaseID'] === $kbID) {
                        $found = true;
                        break;
                    }
                }
                $this->assertTrue($found, $kbID.' expected but not present in apiresponse');
            }
        }
    }

    /**
     * Test /articles/{id}
     *
     * @param array $args
     * @depends testPrepareData
     * @dataProvider getArticleDataProvider
     */
    public function testGetArticle(array $args) {
        $this->api()->setUserID(self::$content['users'][$args['user']]['userID']);
        foreach ($args['kbs'] as $kbKey => $status) {
            $articleID = self::$content['kbs'][$kbKey]['patchArticle']['articleID'];
            if (is_string($status)) {
                $this->expectException($status);
                $responseStatus = $this->api()->get('/articles/' . $articleID)->getStatusCode();
            } else {
                $responseStatus = $this->api()->get('/articles/' . $articleID)->getStatusCode();
                $this->assertEquals($status, $responseStatus);
            }
        }
    }

    /**
     * Data provider for testGetArticle()
     */
    public function getArticleDataProvider(): array {
        return $this->getKnowledgeBaseDataProvider();
    }

    /**
     * Test /articles/{id}/edit
     *
     * @param array $args
     * @depends testPrepareData
     * @dataProvider patchCategoryNameSuccessProvider
     */
    public function testGetEditArticleSuccess(array $args) {
        $this->api()->setUserID(self::$content['users'][$args['user']]['userID']);
        $articleID = self::$content['kbs'][$args['kb']]['patchArticle']['articleID'];
        $responseStatus = $this->api()->get('/articles/'.$articleID.'/edit')->getStatusCode();
        $this->assertEquals(200, $responseStatus);
    }

    /**
     * Test /articles/{id}/edit
     *
     * @param array $args
     * @depends testPrepareData
     * @dataProvider patchCategoryNameFailProvider
     */
    public function testGetEditArticleFail(array $args) {
        $this->api()->setUserID(self::$content['users'][$args['user']]['userID']);
        $articleID = self::$content['kbs'][$args['kb']]['patchArticle']['articleID'];
        $this->expectException($args['exception']);
        $responseStatus = $this->api()->get('/articles/'.$articleID.'/edit')->getStatusCode();
    }
    
    /**
     * Data provider for testGetEditArticle()
     *
     * @return array
     */
    public function getArticleEditDataProvider(): array {
        return $this->patchDataProvider();
    }

    /**
     * Test PATCH /knowledge-bases/{id}
     *
     * @param array $args
     * @depends testPrepareData
     * @dataProvider getKnowledgeBaseDataProvider
     */
    public function testGetKnowledgeBase(array $args) {
        $this->api()->setUserID(self::$content['users'][$args['user']]['userID']);
        foreach ($args['kbs'] as $kbKey => $status) {
            $kbID = self::$content['kbs'][$kbKey]['knowledgeBaseID'];
            if (is_string($status)) {
                $this->expectException($status);
                $responseStatus = $this->api()->get($this->baseUrl . '/' . $kbID)->getStatusCode();
            } else {
                $responseStatus = $this->api()->get($this->baseUrl . '/' . $kbID)->getStatusCode();
                $this->assertEquals($status, $responseStatus);
            }
        }
    }

    /**
     * Data provider for testGetKnowledgeBase()
     */
    public function getKnowledgeBaseDataProvider(): array {
        return [
            'Nothing' => [
                [
                    'user' => 'Nothing',
                    'kbs' => [
                        'KB1' => ForbiddenException::class,
                        'KB2' => ForbiddenException::class,
                        'KB3' => ForbiddenException::class,
                    ]
                ]
            ],
            'View All' => [
                [
                    'user' => 'View All',
                    'kbs' => [
                        'KB1' => 200,
                        'KB2' => 200,
                        'KB3' => 200,
                    ]
                ]
            ],
            'View All Member' => [
                [
                    'user' => 'View All Member',
                    'kbs' => [
                        'KB1' => 200,
                        'KB2' => 200,
                        'KB3' => 200,
                    ]
                ]
            ],
            'View Public' => [
                [
                    'user' => 'View Public',
                    'kbs' => [
                        'KB1' => ForbiddenException::class,
                        'KB2' => ForbiddenException::class,
                        'KB3' => 200,
                    ]
                ]
            ],
            'View KB1' => [
                [
                    'user' => 'View KB1',
                    'kbs' => [
                        'KB1' => 200,
                        'KB2' => ForbiddenException::class,
                        'KB3' => 200,
                    ]
                ]
            ],
            'View KB2' => [
                [
                    'user' => 'View KB2',
                    'kbs' => [
                        'KB1' => ForbiddenException::class,
                        'KB2' => 200,
                        'KB3' => 200,
                    ]
                ]
            ],
        ];
    }

    /**
     * Test PATCH /knowledge-categories/{id} SUCCESS scenarios only.
     */
    public function patchCategoryNameSuccessProvider(): array {
        $data = self::patchData();
        $providerData = [];
        foreach ($data as $testCase => $arr) {
            $args = $arr[0];
            foreach ($args['kbs'] as $kbKey => $status) {
                if (!is_string($status)) {
                    $item['user'] = $args['user'];
                    $item['kb'] = $kbKey;
                    $providerData[$testCase . ' ' . $kbKey] = [$item];
                }
            }
        }
        return $providerData;
    }

    /**
     * Test PATCH /knowledge-categories/{id} FAILED scenarios only
     * @depends testPrepareData
     */
    public function patchCategoryNameFailProvider(): array {
        $data = self::patchData();
        $providerData = [];
        foreach ($data as $testCase => $arr) {
            $args = $arr[0];
            foreach ($args['kbs'] as $kbKey => $status) {
                if (is_string($status)) {
                    $item['user'] = $args['user'];
                    $item['kb'] = $kbKey;
                    $item['exception'] = $status;
                    $providerData[$testCase . ' ' . $kbKey] = [$item];
                }
            }
        }
        return $providerData;
    }

    /**
     * Test PATCH /knowledge-categories/{id}
     *
     * @param array $args
     * @depends testPrepareData
     * @dataProvider patchCategoryNameFailProvider
     */
    public function testPatchCategoryNameFail(array $args) {
        $this->api()->setUserID(self::$content['users'][$args['user']]['userID']);
        $kb = self::$content['kbs'][$args['kb']];
        $name = $kb['patchCategory']['name'].__FUNCTION__;
        $this->expectException($args['exception']);
        $result = $this->api()->patch(
            '/knowledge-categories/'.$kb['patchCategory']['knowledgeCategoryID'],
            ['name' => $name]
        );
    }

    /**
     * Test PATCH /knowledge-categories/{id}
     *
     * @param array $args
     * @depends testPrepareData
     * @dataProvider patchCategoryNameSuccessProvider
     */
    public function testPatchCategoryNameSuccess(array $args) {
        $this->api()->setUserID(self::$content['users'][$args['user']]['userID']);
        $kb = self::$content['kbs'][$args['kb']];
        $name = $kb['patchCategory']['name'].__FUNCTION__;
        $result = $this->api()->patch(
            '/knowledge-categories/'.$kb['patchCategory']['knowledgeCategoryID'],
            ['name' => $name]
        );
        $responseStatus = $result->getStatusCode();
        $body = $result->getBody();
        $this->assertEquals(200, $responseStatus);
        $this->assertEquals($name, $body['name']);
    }

    /**
     * Data provider for testPatchCategoryName()
     */
    public static function patchData(): array {
        return [
            'Nothing' => [
                [
                    'user' => 'Nothing',
                    'kbs' => [
                        'KB1' => ForbiddenException::class,
                        'KB2' => ForbiddenException::class,
                        'KB3' => ForbiddenException::class,
                    ]
                ]
            ],
            'View All' => [
                [
                    'user' => 'View All',
                    'kbs' => [
                        'KB1' => ForbiddenException::class,
                        'KB2' => ForbiddenException::class,
                        'KB3' => 200,
                    ]
                ]
            ],
            'View All Member' => [
                [
                    'user' => 'View All Member',
                    'kbs' => [
                        'KB1' => ForbiddenException::class,
                        'KB2' => 200,
                        'KB3' => 200,
                    ]
                ]
            ],
            'View Public' => [
                [
                    'user' => 'View Public',
                    'kbs' => [
                        'KB1' => ForbiddenException::class,
                        'KB2' => ForbiddenException::class,
                        'KB3' => 200,
                    ]
                ]
            ],
            'View KB1' => [
                [
                    'user' => 'View KB1',
                    'kbs' => [
                        'KB1' => ForbiddenException::class,
                        'KB2' => ForbiddenException::class,
                        'KB3' => 200,
                    ]
                ]
            ],
            'Edit KB1' => [
                [
                    'user' => 'Edit KB1',
                    'kbs' => [
                        'KB1' => 200,
                        'KB2' => ForbiddenException::class,
                        'KB3' => 200,
                    ]
                ]
            ],
            'View KB2' => [
                [
                    'user' => 'View KB2',
                    'kbs' => [
                        'KB1' => ForbiddenException::class,
                        'KB2' => ForbiddenException::class,
                        'KB3' => 200,
                    ]
                ]
            ],
            'Edit KB2' => [
                [
                    'user' => 'Edit KB2',
                    'kbs' => [
                        'KB1' => ForbiddenException::class,
                        'KB2' => 200,
                        'KB3' => 200,
                    ]
                ]
            ],
        ];
    }




    ///
    /// SORRY. Right now messing with the guest user pollutes the other test cases.
    /// As a result, I'm putting it last. Currently there is no time to debug why the pollution is occuring.
    ///

    /**
     * Set our guest role to disallow viewing KBs.
     *
     * @param bool $canView
     */
    private function setupGuest(bool $canView) {
        // Make sure our guest has view permissions
        $this->api()->patch('/roles/' . self::GUEST_ROLE_ID, [
            'permissions' => [
                [
                    'type' => 'global',
                    'permissions' => [
                        'kb.view' => $canView,
                    ]
                ]
            ]
        ]);
        $this->api()->setUserID(\UserModel::GUEST_USER_ID);
    }

    /**
     * Test a scenario where a guest has view permissions.
     *
     * @depends testPrepareData
     */
    public function testGuestCannotView() {
        $this->setupGuest(false);

        try {
            $this->api()->get('/knowledge-bases');
            $this->fail('Should throw exception.');
        } catch (ForbiddenException $e) {
            $this->assertTrue(true);
        }

        try {
            $this->api()->get('/knowledge-bases/' .  self::$content['kbs']['KB1']['knowledgeBaseID']);
            $this->fail('Should throw exception.');
        } catch (ForbiddenException $e) {
            $this->assertTrue(true);
        }
    }
    /**
     * Test a scenario where a guest has view permissions.
     *
     * @depends testPrepareData
     */
    public function testGuestCanView() {
        $this->setupGuest(true);

        // Can view the ones without custom permissions.
        $kbs = $this->api()->get('/knowledge-bases')->getBody();
        $this->assertCount(1, $kbs);

        // Still restricted from here.
        try {
            $this->api()->get('/knowledge-bases/' .  self::$content['kbs']['KB1']['knowledgeBaseID']);
            $this->fail('Should throw exception.');
        } catch (ForbiddenException $e) {
            $this->assertTrue(true);
        }
    }
}
