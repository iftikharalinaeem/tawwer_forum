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
use Vanilla\Models\FsThemeProvider;
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
        $response = $this->api()->patch("themes/".self::$data['newTheme']['themeID'], ['name'=>'custom theme PATCHED']);
        $this->assertEquals(200, $response->getStatusCode());
        $body = $response->getBody();
        $this->assertEquals('custom theme PATCHED', $body['name']);
        $this->assertEquals("<div><!-- HEADER --></div>", $body['assets']['header']);
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
    }
}
