<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\APIv2;

use Gdn_Configuration;
use Gdn_Request;
use Gdn_Upload;
use Vanilla\Addon;
use Vanilla\AddonManager;
use Garden\Container\Reference;
use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\Theme\FsThemeProvider;
use Garden\Web\Exception\ClientException;

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
     * Test POSTing a theme new name. Should fail since there is no dynamic theme provider.
     */
    public function testPostTheme() {
        $response = $this->api()->post(
            "themes",
            [
                'name'=>'custom theme',
                'parentTheme' => 'test',
                'parentVersion' => '1.0',
                "assets" => [
                            "header" => [
                                "data" => "<div><!-- HEADER --></div>",
                                "type" => "html"
                            ],
                            "footer" => [
                                "data" => "<div><!-- FOOTER --></div>",
                                 "type" => "html"
                            ]
                ]
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
        $response = $this->api()->patch(
            "themes/".self::$data['newTheme']['themeID'],
            [
                'name'=>'custom theme PATCHED',
                "assets" => [
                    "header" => [
                        "data" => "<div><!-- HEADER PATCHED --></div>",
                        "type" => "html"
                    ],
                    "footer" => [
                        "data" => "<div><!-- FOOTER --></div>",
                        "type" => "html"
                    ]
                ]
            ]
        );
        $this->assertEquals(200, $response->getStatusCode());
        $body = $response->getBody();
        $this->assertEquals('custom theme PATCHED', $body['name']);
        $this->assertEquals("<div><!-- HEADER PATCHED --></div>", $body['assets']['header']);
        $this->assertEquals("<div><!-- FOOTER --></div>", $body['assets']['footer']);
    }

    /**
     * Test GET theme revisions.
     *
     * @depends testPatchTheme
     */
    public function testThemeRevisions() {
        $response = $this->api()->get("themes/".self::$data['newTheme']['themeID'].'/revisions');
        $this->assertEquals(200, $response->getStatusCode());
        $body = $response->getBody();
        $this->assertEquals(2, count($body));

        self::$data['revisions']['initial'] = $body[0];
        self::$data['revisions']['patched'] = $body[1];

        $this->assertTrue($body[1]['active']);
        $this->assertFalse($body[0]['active']);

        $response = $this->api()->get("themes/".self::$data['newTheme']['themeID']);
        $this->assertEquals(200, $response->getStatusCode());
        $body = $response->getBody();
        $this->assertEquals(self::$data['revisions']['patched']['revisionID'], $body['revisionID']);
        $this->assertEquals("<div><!-- HEADER PATCHED --></div>", $body['assets']['header']);
        $this->assertEquals("<div><!-- FOOTER --></div>", $body['assets']['footer']);

        $response = $this->api()->get(
            "themes/".self::$data['newTheme']['themeID'],
            ['revisionID' => self::$data['revisions']['initial']['revisionID']]
        );
        $this->assertEquals(200, $response->getStatusCode());
        $body = $response->getBody();
        $this->assertEquals(self::$data['revisions']['initial']['revisionID'], $body['revisionID']);
        $this->assertEquals("<div><!-- HEADER --></div>", $body['assets']['header']);
        $this->assertEquals("<div><!-- FOOTER --></div>", $body['assets']['footer']);
    }

    /**
     * Test GET theme revisions.
     *
     * @depends testThemeRevisions
     */
    public function testThemeRevisionRestore() {
        $response = $this->api()->patch(
            "themes/".self::$data['newTheme']['themeID'],
            [
                'revisionID' => self::$data['revisions']['initial']['revisionID']
            ]
        );
        $this->assertEquals(200, $response->getStatusCode());
        $body = $response->getBody();

        $response = $this->api()->get("themes/".self::$data['newTheme']['themeID'].'/revisions');
        $this->assertEquals(200, $response->getStatusCode());
        $body = $response->getBody();
        $this->assertEquals(2, count($body));

        $this->assertTrue($body[0]['active']);
        $this->assertFalse($body[1]['active']);

        $response = $this->api()->get("themes/".self::$data['newTheme']['themeID']);
        $this->assertEquals(200, $response->getStatusCode());
        $body = $response->getBody();
        $this->assertEquals(self::$data['revisions']['initial']['revisionID'], $body['revisionID']);
        $this->assertEquals("<div><!-- HEADER --></div>", $body['assets']['header']);
        $this->assertEquals("<div><!-- FOOTER --></div>", $body['assets']['footer']);

        $response = $this->api()->get(
            "themes/".self::$data['newTheme']['themeID'],
            ['revisionID' => self::$data['revisions']['patched']['revisionID']]
        );
        $this->assertEquals(200, $response->getStatusCode());
        $body = $response->getBody();
        $this->assertEquals(self::$data['revisions']['patched']['revisionID'], $body['revisionID']);
        $this->assertEquals("<div><!-- HEADER PATCHED --></div>", $body['assets']['header']);
        $this->assertEquals("<div><!-- FOOTER --></div>", $body['assets']['footer']);
    }

    /**
     * Test PATCH endpoint used to rename revision
     *
     * @depends testPostTheme
     */
    public function testThemeRevisionRename() {
        $response = $this->api()->patch(
            "themes/".self::$data['newTheme']['themeID'],
            [
                'revisionID' => self::$data['revisions']['patched']['revisionID'],
                'revisionName' => 'rev 2020.001'
            ]
        );
        $this->assertEquals(200, $response->getStatusCode());
        $body = $response->getBody();
        $this->assertEquals('custom theme PATCHED', $body['name']);
        $this->assertEquals('rev 2020.001', $body['revisionName']);
        $this->assertEquals("<div><!-- HEADER PATCHED --></div>", $body['assets']['header']);
        $this->assertEquals("<div><!-- FOOTER --></div>", $body['assets']['footer']);
    }
}
