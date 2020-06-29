<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\APIv2;

use Garden\Web\Exception\ClientException;
use Gdn_Configuration;
use Vanilla\Theme\ThemeService;

/**
 * Test the /api/v2/themes endpoints.
 */
class ThemesDbTest extends AbstractAPIv2Test {

    /**
     * @var string The resource route.
     */
    protected $baseUrl = "/themes";

    protected static $addons = ['vanilla', 'themingapi'];

    private static $data = [];

    /**
     * Ensure we have clean theme revision table.
     */
    public static function setupBeforeClass(): void {
        parent::setupBeforeClass();
        \Gdn::sql()->truncate('themeRevision');
        \Gdn::sql()->truncate('theme');
        \Gdn::sql()->truncate('themeAsset');
    }

    /**
     * Test POSTing a theme new name. Should fail since there is no dynamic theme provider.
     */
    public function testPostTheme() {
        $response = $this->api()->post(
            "themes",
            [
                'name' => 'custom theme',
                'parentTheme' => 'test',
                'parentVersion' => '1.0',
                "assets" => [
                    "header" => [
                        "data" => "<div><!-- HEADER --></div>",
                        "type" => "html",
                    ],
                    "footer" => [
                        "data" => "<div><!-- FOOTER --></div>",
                        "type" => "html",
                    ],
                ],
            ]
        );
        $this->assertEquals(201, $response->getStatusCode());
        self::$data['newTheme'] = $response->getBody();
    }

    /**
     * Test PATCHing a theme. Should fail since there is no dynamic theme provider.
     *
     * @depends testPostTheme
     */
    public function testPatchTheme() {
        // So that our sort dates get a consistent order.
        sleep(1);
        $response = $this->api()->patch(
            "themes/" . self::$data['newTheme']['themeID'],
            [
                'name' => 'custom theme PATCHED',
                "assets" => [
                    "header" => [
                        "data" => "<div><!-- HEADER PATCHED --></div>",
                        "type" => "html",
                    ],
                    "footer" => [
                        "data" => "<div><!-- FOOTER --></div>",
                        "type" => "html",
                    ],
                ],
            ]
        );
        $this->assertEquals(200, $response->getStatusCode());
        $body = $response->getBody();
        $this->assertEquals('custom theme PATCHED', $body['name']);
        $this->assertEquals("<div><!-- HEADER PATCHED --></div>", $body['assets']['header']['data']);
        $this->assertEquals("<div><!-- FOOTER --></div>", $body['assets']['footer']['data']);
    }

    /**
     * Test GET theme revisions.
     *
     * @depends testPatchTheme
     */
    public function testThemeRevisions() {
        $response = $this->api()->get("themes/" . self::$data['newTheme']['themeID'] . '/revisions');
        $this->assertEquals(200, $response->getStatusCode());
        $body = $response->getBody();
        $this->assertEquals(2, count($body));

        // Latest inserted revisions are first.
        self::$data['revisions']['initial'] = $body[1];
        self::$data['revisions']['patched'] = $body[0];

        $this->assertTrue($body[0]['active']);
        $this->assertFalse($body[1]['active']);

        $response = $this->api()->get("themes/" . self::$data['newTheme']['themeID'], ['expand' => true]);
        $this->assertEquals(200, $response->getStatusCode());
        $body = $response->getBody();
        $this->assertEquals(self::$data['revisions']['patched']['revisionID'], $body['revisionID']);
        $this->assertEquals("<div><!-- HEADER PATCHED --></div>", $body['assets']['header']['data']);
        $this->assertEquals("<div><!-- FOOTER --></div>", $body['assets']['footer']['data']);

        $response = $this->api()->get(
            "themes/" . self::$data['newTheme']['themeID'],
            ['revisionID' => self::$data['revisions']['initial']['revisionID'], 'expand' => true]
        );
        $this->assertEquals(200, $response->getStatusCode());
        $body = $response->getBody();
        $this->assertEquals(self::$data['revisions']['initial']['revisionID'], $body['revisionID']);
        $this->assertEquals("<div><!-- HEADER --></div>", $body['assets']['header']['data']);
        $this->assertEquals("<div><!-- FOOTER --></div>", $body['assets']['footer']['data']);
    }

    /**
     * Test GET theme revisions.
     *
     * @depends testThemeRevisions
     */
    public function testThemeRevisionRestore() {
        $response = $this->api()->patch(
            "themes/" . self::$data['newTheme']['themeID'],
            [
                'revisionID' => self::$data['revisions']['initial']['revisionID'],
            ]
        );
        $this->assertEquals(200, $response->getStatusCode());
        $body = $response->getBody();

        $response = $this->api()->get("themes/" . self::$data['newTheme']['themeID'] . '/revisions');
        $this->assertEquals(200, $response->getStatusCode());
        $body = $response->getBody();
        $this->assertEquals(2, count($body));

        $this->assertTrue($body[1]['active']);
        $this->assertFalse($body[0]['active']);

        $response = $this->api()->get("themes/" . self::$data['newTheme']['themeID'], ['expand' => true]);
        $this->assertEquals(200, $response->getStatusCode());
        $body = $response->getBody();
        $this->assertEquals(self::$data['revisions']['initial']['revisionID'], $body['revisionID']);
        $this->assertEquals("<div><!-- HEADER --></div>", $body['assets']['header']['data']);
        $this->assertEquals("<div><!-- FOOTER --></div>", $body['assets']['footer']['data']);

        $response = $this->api()->get(
            "themes/" . self::$data['newTheme']['themeID'],
            ['revisionID' => self::$data['revisions']['patched']['revisionID'], 'expand' => true]
        );
        $this->assertEquals(200, $response->getStatusCode());
        $body = $response->getBody();
        $this->assertEquals(self::$data['revisions']['patched']['revisionID'], $body['revisionID']);
        $this->assertEquals("<div><!-- HEADER PATCHED --></div>", $body['assets']['header']['data']);
        $this->assertEquals("<div><!-- FOOTER --></div>", $body['assets']['footer']['data']);
    }

    /**
     * Test PATCH endpoint used to rename revision
     *
     * @depends testThemeRevisions
     */
    public function testThemeRevisionRename() {
        $response = $this->api()->patch(
            "themes/" . self::$data['newTheme']['themeID'],
            [
                'revisionID' => self::$data['revisions']['patched']['revisionID'],
                'revisionName' => 'updated revision name',
            ]
        );
        $this->assertEquals(200, $response->getStatusCode());
        $body = $response->getBody();
        $this->assertEquals('custom theme PATCHED', $body['name']);
        $this->assertEquals('updated revision name', $body['revisionName']);
        $this->assertEquals("<div><!-- HEADER PATCHED --></div>", $body['assets']['header']['data']);
        $this->assertEquals("<div><!-- FOOTER --></div>", $body['assets']['footer']['data']);
    }


    /**
     * Test that putting and getting of assets works as expected.
     *
     * No extension provided for this.
     *
     * @param string $assetName
     * @param array|string $assetData
     * @param string $expectedContentType
     *
     * @dataProvider providePutGet
     */
    public function testPutGet(string $assetName, $assetData, string $expectedContentType = 'application/json; charset=utf-8') {
        $theme = $this->api()->post('/themes', [
            'name' => 'test theme',
            'parentTheme' => 'theme-foundation',
            'parentVersion' => '1.0.0',
        ])->getBody();

        $themeID = $theme['themeID'];

        $url = "/themes/$themeID/assets/$assetName";
        $this->api()->put($url, $assetData);
        $response = $this->api()->get($url);
        $this->assertEquals($expectedContentType, $response->getHeader('Content-Type'));
        $body = $response->getBody();

        if (is_array($body) && isset($body['url'])) {
            unset($body['url']);
        }

        $this->assertEquals($assetData, $body);
    }

    /**
     * @return array[]
     */
    public function providePutGet(): array {
        return [
            'header' => [
                'header',
                [
                    'data' => '<div>Hello header</div>',
                    'type' => 'html',
                    'content-type' => 'text/html',
                ]
            ],
            'header.html' => [
                'header.html',
                '<div>Hello header</div>',
                'text/html',
            ],
            'footer' => [
                'footer',
                [
                    'data' => '<div>Hello footer</div>',
                    'type' => 'html',
                    'content-type' => 'text/html',
                ]
            ],
            'footer.html' => [
                'footer.html',
                '<div>Hello footer</div>',
                'text/html',
            ],
            'javascript' => [
                'javascript',
                [
                    'data' => 'console.log("Hello world");',
                    'type' => 'js',
                    'content-type' => 'application/javascript',
                ]
            ],
            'javascript.js' => [
                'javascript.js',
                'console.log("Hello world");',
                'application/javascript',
            ],
            'styles' => [
                'styles',
                [
                    'data' => 'console.log("Hello world");',
                    'type' => 'css',
                    'content-type' => 'text/css',
                ]
            ],
            'styles.css' => [
                'styles.css',
                '.header { background: white }',
                'text/css',
            ],
            'variables' => [
                'variables',
                [
                    'data' => [
                        'global' => [
                            'colors' => [
                                'bg' => "#fff"
                            ]
                        ]
                    ],
                    'type' => 'json',
                    'content-type' => 'application/json',
                ]
            ],
            'variables.json' => [
                'variables.json',
                [
                    'global' => [
                        'colors' => [
                            'bg' => "#fff"
                        ]
                    ]
                ],
                'application/json',
            ],
            'scripts' => [
                'scripts',
                [
                    'data' => [
                        [ 'url' => 'https://cdn.js.com/jquery.js' ]
                    ],
                    'type' => 'json',
                    'content-type' => 'application/json',
                ]
            ],
            'scripts.json' => [
                'scripts.json',
                [
                    [ 'url' => 'https://cdn.js.com/jquery.js' ]
                ],
                'application/json',
                'content-type' => 'application/json',
            ],
        ];
    }

    /**
     * Test that we can patch variables.
     */
    public function testPatchVariables() {
        $themeID = $this->createTheme();

        $url = "/themes/$themeID/assets/variables.json";
        $this->api()->put($url, ['hello' => 'world']);
        $this->api()->patch($url, ['hello2' => 'world2']);
        $this->api()->patch("/themes/$themeID/assets/variables", [
            'type' => 'json',
            'data' => [
                'hello3' => 'world3',
            ],
        ]);

        $result = $this->api()->get($url)->getBody();
        $this->assertEquals([
            'hello' => 'world',
            'hello2' => 'world2',
            'hello3' => 'world3',
        ], $result);
    }

    /**
     * Test that patching variables replaces arrays instead of merging them.
     */
    public function testPatchArrayMerge() {
        $themeID = $this->createTheme();

        $url = "/themes/$themeID/assets/variables.json";
        $this->api()->put($url, [
            'navLinks' => ['link1', 'link2'],
        ]);
        $this->api()->patch($url, [
            'navLinks' => ['link1.1'],
        ]);

        $result = $this->api()->get($url)->getBody();
        $this->assertEquals([
            'navLinks' => ['link1.1', 'link2'],
        ], $result, 'Arrays are replaced instead of merged');
    }

    /**
     * Test bad asset.
     *
     * @param string $assetName
     * @param mixed $asset
     *
     * @dataProvider provideBadAssets
     */
    public function testAssetInputValidationPost(string $assetName, $asset) {
        $this->expectException(ClientException::class);
        $themeID = $this->createTheme([
            'assets' => [
                $assetName => $asset,
            ]
        ]);
    }


    /**
     * @return array
     */
    public function provideBadAssets(): array {
        return [
            'bad type' => [
                'variables',
                [
                    'type' => 'badType',
                    'data' => 'asdfasdf',
                ],
            ],
            'bad data' => [
                'variables',
                [
                    'type' => 'json',
                    'data' => 'asdfaasdf',
                ],
            ],
        ];
    }

    /**
     * Test bad asset.
     *
     * @param string $assetName
     * @param mixed $asset
     *
     * @dataProvider provideBadAssetsPut
     */
    public function testAssetInputValidationPutAsset(string $assetName, $asset) {
        $themeID = $this->createTheme([]);

        $this->expectException(ClientException::class);
        $this->api()->put("/api/v2/themes/$themeID/assets/$assetName", $asset);
    }

    /**
     * Test PUT /current when DB
     */
    public function testPutCurrentConfigValues() {
        /** @var Gdn_Configuration $config */
        $config = static::container()->get(Gdn_Configuration::class);
        $config->set('Garden.Theme', 'keystone');

        $themeID = $this->createTheme([]);

        $this->api()->put("/themes/current", ["themeID" => $themeID]);

        $theme = $config->get('Garden.Theme');
        $currentTheme = $config->get('Garden.CurrentTheme');

        $this->assertEquals(ThemeService::FOUNDATION_THEME_KEY, $theme);
        $this->assertEquals($themeID, $currentTheme);
    }

    /**
     * @return array
     */
    public function provideBadAssetsPut(): array {
        return [
            'bad type' => [
                'variables',
                [
                    'type' => 'badType',
                    'data' => 'asdfasdf',
                ],
            ],
            'bad data' => [
                'variables',
                [
                    'type' => 'json',
                    'data' => 'asdfaasdf',
                ],
            ],
            'bad json' => [
                'variables.json',
                'asasdfasdf'
            ],
            'bad json 2' => [
                'variables.json',
                '{asdf  asdf ['
            ],
        ];
    }


    /**
     * Create a theme and return the ID.
     *
     * @param array $overrides
     *
     * @return int The theme ID.
     */
    private function createTheme(array $overrides = []): int {
        $theme = $this->api()->post('/themes', [
            'name' => 'test theme',
            'parentTheme' => 'theme-foundation',
            'parentVersion' => '1.0.0',
        ] + $overrides)->getBody();

        $themeID = $theme['themeID'];
        return $themeID;
    }
}
