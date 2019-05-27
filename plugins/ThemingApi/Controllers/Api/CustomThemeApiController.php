<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

use Garden\Schema\Schema;
use Garden\Web\Data;
use Garden\Web\Exception\NotFoundException;
use Vanilla\AddonManager;
use Vanilla\Addon;
use Vanilla\Models\ThemeModel;
use Vanilla\Models\ThemeAssetModel;
use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\Exception\Database\NoResultsException;
use Garden\Web\Exception\ClientException;
use Vanilla\Theme\Asset;
use Vanilla\Theme\FontsAsset;
use Vanilla\Theme\HtmlAsset;
use Vanilla\Theme\JsonAsset;
use Vanilla\Theme\StyleAsset;
use Vanilla\Utility\InstanceValidatorSchema;
use Vanilla\Theme\ScriptsAsset;
use Vanilla\Theme\ImageAsset;
use Vanilla\Theme\CustomThemeProviderInterface;

/**
 * API Controller for the `/themes` resource.
 */
class CustomThemeApiController extends AbstractApiController implements CustomThemeProviderInterface {

    const ASSET_LIST = [
        "header" => [
            "type" => "html",
            "file" => "header.html",
            "default" => "",
            "mime-type" => "text/html"
        ],
        "footer" => [
            "type" => "html",
            "file" => "footer.html",
            "default" => "",
            "mime-type" => "text/html"
        ],
        "variables" => [
            "type" => "json",
            "file" => "variables.json",
            "default" => "{}",
            "mime-type" => "application/json"
        ],
        "fonts" => [
            "type" => "json",
            "file" => "fonts.json",
            "default" => "[]",
            "mime-type" => "application/json"
        ],
        "scripts" => [
            "type" => "json",
            "file" => "scripts.json",
            "default" => "[]",
            "mime-type" => "application/json"
        ],
        "styles" => [
            "type" => "css",
            "file" => "styles.css",
            "default" => "",
            "mime-type" => "text/css"
        ],
        "javascript" => [
            "type" => "js",
            "file" => "javascript.js",
            "default" => "",
            "mime-type" => "application/javascript"
        ],
    ];

    /** @var ConfigurationInterface */
    private $config;

    /** @var ThemeModel */
    private $themeModel;

    /** @var ThemeAssetModel */
    private $themeAssetModel;

    /** @var Gdn_Request */
    private $request;

    /**
     * @inheritdoc
     */
    public function __construct(
        ThemeModel $themeModel,
        ThemeAssetModel $themeAssetModel,
        Gdn_Request $request,
        ConfigurationInterface $config) {
        $this->config = $config;
        $this->request = $request;
        $this->themeModel = $themeModel;
        $this->themeAssetModel = $themeAssetModel;
    }

    /**
     * Create new theme.
     *
     * @param array $body Array of incoming params.
     *        fields: name (required)
     * @return array
     */
    public function post(array $body): array {
        $this->permission("Garden.Settings.Manage");

        $in = $this->themePostSchema('in')->setDescription('Create new custom theme.');

        $out = $this->themeResultSchema('out');

        $body = $in->validate($body);

        $themeID = $this->themeModel->insert($body);

        $theme = $this->themeModel->selectSingle(['themeID' => $themeID], ['select' => ['themeID', 'name', 'current', 'dateUpdated']]);

        $normalizedTheme = $this->normalizeTheme(
            $theme,
            $this->themeAssetModel->get(['themeID' => $themeID], ['select' => ['assetKey', 'data']])
        );
        $theme = $out->validate($normalizedTheme);
        return $theme;
    }

    /**
     * Update theme  by ID.
     *
     * @param int $themeID Theme ID
     * @param array $body Array of incoming params.
     *        fields: name (required)
     * @return array
     */
    public function patch(int $themeID, array $body): array {
        $this->permission("Garden.Settings.Manage");
        $in = $this->themePostSchema('in')->setDescription('Update theme name.');
        $out = $this->themeResultSchema('out');
        $body = $in->validate($body);

        //check if theme exists
        try {
            $theme = $this->themeModel->selectSingle(['themeID' => $themeID]);
        } catch (NoResultsException $e) {
            throw new NotFoundException('Theme with ID: ' . $themeID . ' not found!');
        }

        $this->themeModel->update($body, ['themeID' => $themeID]);

        $theme = $this->themeModel->selectSingle(['themeID' => $themeID], ['select' => ['themeID', 'name', 'current', 'dateUpdated']]);
        $normalizedTheme = $this->normalizeTheme(
            $theme,
            $this->themeAssetModel->get(['themeID' => $themeID], ['select' => ['assetKey', 'data']])
        );
        $theme = $out->validate($normalizedTheme);
        return $theme;
    }

    /**
     * Delete theme by ID.
     *
     * @param int $themeID Theme ID
     */
    public function delete(int $themeID) {
        $this->permission("Garden.Settings.Manage");
        $in = $this->schema([],'in')->setDescription('Delete theme.');
        $out = $this->schema([],'out');

        //check if theme exists
        try {
            $theme = $this->themeModel->selectSingle(['themeID' => $themeID]);
        } catch (NoResultsException $e) {
            throw new NotFoundException('Theme with ID: ' . $themeID . ' not found!');
        }

        $this->themeModel->delete(['themeID' => $themeID]);
    }

    /**
     * @inheritdoc
     */
    public function put_current(array $body): array {
        $this->permission("Garden.Settings.Manage");
        $in = $this->themePutCurrentSchema('in')->setDescription('Set current theme.');
        $out = $this->themeResultSchema('out');
        $body = $in->validate($body);

        $theme = $this->themeModel->setCurrentTheme($body['themeID']);
        $theme = $out->validate($theme);
        return $theme;
    }

    /**
     * @inheritdoc
     */
    public function get_current(): ?array {
        $this->permission();
        $in = $this->schema([], 'in')->setDescription('Get current theme.');
        $out = $this->themeResultSchema('out');
        try {
            $theme = $this->themeModel->selectSingle(['current' => 1]);
        } catch (NoResultsException $e) {
            return null;
        }

        $normalizedTheme = $this->normalizeTheme(
            $theme,
            $this->themeAssetModel->get(['themeID' => $theme['themeID']], ['select' => ['assetKey', 'data']])
        );
        $theme = $out->validate($normalizedTheme);
        return $theme;
    }


    /**
     * Get a theme with assets.
     *
     * @param string $themeKey The unique theme key or theme ID.
     * @return array
     */
    public function get(int $themeID): array {
        $this->permission();

        $out = $this->themeResultSchema('out');
        try {
            $theme = $this->themeModel->selectSingle(['themeID' => $themeID], ['select' => ['themeID', 'name', 'current', 'dateUpdated']]);
        } catch (NoResultsException $e) {
            throw new NotFoundException('Theme with ID: ' . $themeID . ' not found!');
        }

        $normalizedTheme = $this->normalizeTheme(
            $theme,
            $this->themeAssetModel->get(['themeID' => $themeID], ['select' => ['assetKey', 'data']])
        );

        $result = $out->validate($normalizedTheme);

        return $result;
    }


    /**
     * Get all theme assets
     *
     * @param array $theme
     * @param array $assets
     * @return array
     */
    private function normalizeTheme(array $theme, array $assets): array {
        $assets = array_combine(array_column($assets, 'assetKey'), array_column($assets, 'data'));

        $res = [
            "assets" => $assets,
            'themeID' => $theme['themeID'],
            'name' => $theme['name'],
            'current' => $theme['current'],
            'type' => 'themeDB',
            'version' => $theme['version'] ?? crc32($theme['dateUpdated']->format('Y-m-d H:i:s'))
        ];

        //die(print_r)
        $res["assets"] = $this->getDefaultAssets($theme);

        $primaryAssets = array_intersect_key(
            $assets,
            array_flip(["fonts", "footer", "header", "scripts", "variables"])
        );

        foreach ($primaryAssets as $assetKey => $asset) {
            $res["assets"][$assetKey] = $this->generateAsset($assetKey, $asset, $theme);
        }

        // Allow addons to add their own variable overrides. Should be moved into the model when the asset generation is refactored.
        $additionalVariables = [];
        foreach ($this->themeModel->getVariableProviders() as $variableProvider) {
            $additionalVariables = $variableProvider->getVariables() + $additionalVariables;
        }
        if ($additionalVariables) {
            if (!array_key_exists("variables", $res["assets"]) || !($res["assets"]["variables"] instanceof JsonAsset)) {
                $variables = [];
            } else {
                $variables = json_decode($res["assets"]["variables"]->getData(), true) ?? [];
            }

            $variables = $additionalVariables + $variables;
            $res["assets"]["variables"] = new JsonAsset(json_encode($variables));
        }

        $logos = [
            "logo" => "Garden.Logo",
            "mobileLogo" => "Garden.MobileLogo",
        ];
        foreach ($logos as $logoName => $logoConfig) {
            if ($logo = $this->config->get($logoConfig)) {
                $logoUrl = Gdn_Upload::url($logo);
                $res["assets"][$logoName] = new ImageAsset($logoUrl);
            }
        }

        return $res;
    }

    public function getDefaultAssets(array $theme): array {
        $assets = [];
        foreach (self::ASSET_LIST as $assetKey => $assetDefinition) {
            $assets[$assetKey] =  $this->generateAsset($assetKey, $assetDefinition['default']);
        }
        $assets['styles'] = $this->request->url('/api/v2/custom-theme/'.$theme['themeID'].'/styles.css', true);
        $assets['javascript'] = $this->request->url('/api/v2/custom-theme/'.$theme['themeID'].'/javascript.js', true);
        return $assets;
    }

    /**
     * Generate an asset object, given an asset array.
     *
     * @param string $key
     * @param array $asset
     * @param Addon $theme
     * @return Asset
     */
    private function generateAsset(string $key, string $data): ?Asset {
        $type = self::ASSET_LIST[$key]["type"];

        switch ($type) {
            case "html":
                return new HtmlAsset($data);
            case "json":
                if ($key === "fonts") {
                    return new FontsAsset(json_decode($data, true));
                } elseif ($key === "scripts") {
                    return new ScriptsAsset(json_decode($data, true));
                } else {
                    return new JsonAsset($data);
                }
            case "js":
            case "css":
                return new StyleAsset($data);
            default:
                return null;
        }
    }

    /**
     * PUT theme asset (update existing or create new if asset does not exist).
     *
     * @param int $themeID The unique theme ID.
     * @param string $assetKey Unique asset key (ex: header.html, footer.html, fonts.json, styles.css)
     * @param array $body Array of incoming params.
     *              Should have 'data' key with content for asset.
     *
     * @return array
     */
    public function put_assets(int $themeID, string $assetKey, array $body): array {
        $this->permission("Garden.Settings.Manage");

        $in = $this->schema($this->assetsPutSchema(), 'in')->setDescription('PUT theme asset.');
        $out = $this->schema($this->assetsSchema(), 'out');

        $body = $in->validate($body);
        $pathInfo = pathinfo($assetKey);
        if (isset(self::ASSET_LIST[$pathInfo['filename']])) {
            if ($pathInfo['basename'] === self::ASSET_LIST[$pathInfo['filename']]['file']) {
                $asset = $this->themeAssetModel->setAsset($themeID, $pathInfo['filename'],$body['data']);
            } else {
                throw new ClientException('Unknown asset file name: "'.$pathInfo['basename'].'".'.
                'Try: '.self::ASSET_LIST[$pathInfo['filename']]['file']);
            }
        } else {
            throw new \Garden\Schema\ValidationException('Unknown asset "'.$pathInfo['filename'].'" field.'.
            'Should be one of: '.implode(array_column(self::ASSET_LIST, 'file')));
        }

        $asset = $out->validate([$pathInfo['filename'] => $this->generateAsset($pathInfo['filename'], $asset['data'])]);
        return $asset;
    }

    /**
     * DELETE theme asset.
     *
     * @param int $themeID The unique theme ID.
     * @param string $assetKey Asset key (ex: header.html, footer.html, fonts.json, styles.css)
     */
    public function delete_assets(int $themeID, string $assetKey) {
        $this->permission("Garden.Settings.Manage");

        $pathInfo = pathinfo($assetKey);
        if (isset(self::ASSET_LIST[$pathInfo['filename']])) {
            if ($pathInfo['basename'] === self::ASSET_LIST[$pathInfo['filename']]['file']) {
                $this->themeAssetModel->deleteAsset($themeID, $pathInfo['filename']);
            } else {
                throw new ClientException('Unknown asset file name: "'.$pathInfo['basename'].'".'.
                    'Try: '.self::ASSET_LIST[$pathInfo['filename']]['file']);
            }
        } else {
            throw new \Garden\Schema\ValidationException('Unknown asset "'.$pathInfo['filename'].'" field.'.
                'Should be one of: '.implode(array_column(self::ASSET_LIST, 'file')));
        }
    }

    /**
     * Get theme asset.
     *
     * @param int $id The unique theme key or theme ID (ex: keystone).
     * @param string $assetKey Asset key (ex: header, footer, fonts, styles)
     *        Note: assetKey can be filename (ex: header.html, styles.css)
     *              in that case file content returned instaed of json structure
     * @link https://github.com/vanilla/roadmap/blob/master/theming/theming-data.md#api
     *
     * @return array|Data
     */
    public function get_assets(int $id, string $assetKey) {
        $this->permission();

        $in = $this->schema([],'in')->setDescription('Get theme assets.');
        $out = $this->schema([], 'out');

        $pathInfo =  pathinfo($assetKey);
        if (isset(self::ASSET_LIST[$pathInfo['filename']])) {
            if ($pathInfo['basename'] === self::ASSET_LIST[$pathInfo['filename']]['file']) {
                try {
                    $asset = $this->themeAssetModel->getAsset($id, $pathInfo['filename']);
                    $content = $asset['data'];
                } catch (NoResultsException $e) {
                    $content = self::ASSET_LIST[$assetKey]['default'];
                }
            } else {
                throw new ClientException('Unknown asset file name: "'.$pathInfo['basename'].'".'.
                    'Try: '.self::ASSET_LIST[$pathInfo['filename']]['file']);
            }
        } else {
            throw new \Garden\Schema\ValidationException('Unknown asset "'.$pathInfo['filename'].'" field.'.
                'Should be one of: '.implode(array_column(self::ASSET_LIST, 'file')));
        }

        $result = (new Data($content))->setHeader("Content-Type", self::ASSET_LIST[$pathInfo['filename']]['mime-type']);
        return $result;
    }

    /**
     * POST theme schema
     *
     * @param string $type
     * @return Schema
     */
    private function themePostSchema(string $type = 'in'): Schema {
        $schema = $this->schema(
            Schema::parse([
                'name:s' => [
                    'description' => 'Custom theme name.',
                ],
            ]),
            $type
        );
        return $schema;
    }

    /**
     * PUT current theme schema
     *
     * @param string $type
     * @return Schema
     */
    private function themePutCurrentSchema(string $type = 'in'): Schema {
        $schema = $this->schema(
            Schema::parse([
                'themeID:i' => [
                    'description' => 'Theme ID.',
                ],
            ]),
            $type
        );
        return $schema;
    }

    /**
     * Result theme schema
     *
     * @param string $type
     * @return Schema
     */
    private function themeResultSchema(string $type = 'out'): Schema {
        $schema = $this->schema(
            Schema::parse([
                'themeID:i',
                'name:s',
                'version:s',
                'current:b?',
                'assets?' => $this->assetsSchema()
            ]),
            $type
        );
        return $schema;
    }

    /**
     * Get 'assets' schema
     *
     * @return Schema
     */
    private function assetsSchema(): Schema {
        $schema = Schema::parse([
            "header?" => new InstanceValidatorSchema(HtmlAsset::class),
            "footer?" => new InstanceValidatorSchema(HtmlAsset::class),
            "variables?" => new InstanceValidatorSchema(JsonAsset::class),
            "fonts?" => new InstanceValidatorSchema(FontsAsset::class),
            "scripts?" => new InstanceValidatorSchema(ScriptsAsset::class),
            "styles:s?",
            "javascript:s?",
            "logo?" => new InstanceValidatorSchema(ImageAsset::class),
            "mobileLogo?" => new InstanceValidatorSchema(ImageAsset::class),
        ])->setID('themeAssetsSchema');
        return $schema;
    }

    /**
     * PUT 'assets' schema
     *
     * @return Schema
     */
    private function assetsPutSchema(): Schema {
        $schema = Schema::parse([
            "data:s",
        ])->setID('themeAssetsPutSchema');
        return $schema;
    }


}
