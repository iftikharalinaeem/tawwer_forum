<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\ThemingApi;

use Gdn_Upload;
use Vanilla\Theme\ThemeProviderInterface;
use Vanilla\ThemingApi\Models\ThemeModel;
use Vanilla\ThemingApi\Models\ThemeAssetModel;
use Vanilla\Models\ThemeVariablesTrait;
use Vanilla\Exception\Database\NoResultsException;
use Garden\Web\Exception\NotFoundException;
use Gdn_Request;
use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\Theme\Asset;
use Vanilla\Theme\FontsAsset;
use Vanilla\Theme\HtmlAsset;
use Vanilla\Theme\JsonAsset;
use Vanilla\Theme\StyleAsset;
use Vanilla\Theme\ScriptsAsset;
use Vanilla\Theme\JavascriptAsset;
use Vanilla\Theme\ImageAsset;

/**
 * Class DbThemeProvider
 */
class DbThemeProvider implements ThemeProviderInterface {
    use ThemeVariablesTrait;
    /**
     * @var ThemeAssetModel
     */
    private $themeAssetModel;

    /**
     * @var ThemeModel
     */
    private $themeModel;

    /** @var ConfigurationInterface */
    private $config;

    /** @var Gdn_Request */
    private $request;

    /**
     * DbThemeProvider constructor.
     *
     * @param ThemeAssetModel $themeAssetModel
     * @param ThemeModel $themeModel
     * @param ConfigurationInterface $config
     * @param Gdn_Request $request
     */
    public function __construct(
        ThemeAssetModel $themeAssetModel,
        ThemeModel $themeModel,
        ConfigurationInterface $config,
        Gdn_Request $request
    ) {
        $this->themeAssetModel = $themeAssetModel;
        $this->themeModel = $themeModel;
        $this->config = $config;
        $this->request = $request;
    }

    /**
     * @inheritdoc
     */
    public function themeKeyType(): int {
        return self::TYPE_DB;
    }

    /**
     * @inheritdoc
     */
    public function getThemeWithAssets($themeKey): array {
        try {
            $theme = $this->normalizeTheme(
                $this->themeModel->selectSingle(['themeID' => $themeKey], ['select' => ['themeID', 'name', 'current', 'dateUpdated']]),
                $this->themeAssetModel->get(['themeID' => $themeKey], ['select' => ['assetKey', 'data']])
            );
        } catch (NoResultsException $e) {
            throw new NotFoundException('Theme with ID: ' . $themeKey . ' not found!');
        }

        return $theme;
    }

    /**
     * @inheritDoc
     */
    public function getAllThemes(): array {
        $dbThemes = $this->themeModel->get();

        $allDbThemes = [];
        foreach ($dbThemes as $dbTheme) {

            $allDbThemes[] = $this->getThemeWithAssets($dbTheme["themeID"]);
        }
        return $allDbThemes;
    }

    /**
     * @inheritdoc
     */
    public function postTheme(array $body): array {
        $themeID = $this->themeModel->insert($body);

        $theme = $this->themeModel->selectSingle(['themeID' => $themeID], ['select' => ['themeID', 'name', 'current', 'dateUpdated']]);

        return $this->normalizeTheme($theme, []);
    }

    /**
     * @inheritdoc
     */
    public function patchTheme(int $themeID, array $body): array {
        //check if theme exists
        try {
            $theme = $this->themeModel->selectSingle(['themeID' => $themeID]);
        } catch (NoResultsException $e) {
            throw new NotFoundException('Theme with ID: ' . $themeID . ' not found!');
        }

        $this->themeModel->update($body, ['themeID' => $themeID]);

        $theme = $this->themeModel->selectSingle(
            ['themeID' => $themeID],
            ['select' => ['themeID', 'name', 'current', 'dateUpdated']]
        );
        return $this->normalizeTheme(
            $theme,
            $this->themeAssetModel->get(['themeID' => $themeID], ['select' => ['assetKey', 'data']])
        );
    }

    /**
     * @inheritdoc
     */
    public function deleteTheme(int $themeID) {
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
    public function setCurrent(int $themeID): array {
        try {
            $theme = $this->normalizeTheme(
                $this->themeModel->setCurrentTheme($themeID),
                $this->themeAssetModel->get(['themeID' => $themeID], ['select' => ['assetKey', 'data']])
            );
        } catch (NoResultsException $e) {
            throw new NotFoundException('Theme with ID: ' . $themeID . ' not found!');
        }
        return $theme;
    }

    /**
     * @inheritdoc
     */
    public function getCurrent(): ?array {
        try {
            $theme = $this->themeModel->selectSingle(['current' => 1]);
        } catch (NoResultsException $e) {
            return null;
        }

        return $this->normalizeTheme(
            $theme,
            $this->themeAssetModel->get(['themeID' => $theme['themeID']], ['select' => ['assetKey', 'data']])
        );
    }

    /**
     * @inheritdoc
     */
    public function setAsset(int $themeID, string $assetKey, string $data): array {
        $asset = $this->themeAssetModel->setAsset($themeID, $assetKey, $data);
        return [$assetKey => $this->generateAsset($assetKey, $asset['data'])];
    }

    /**
     * @inheritdoc
     */
    public function sparseAsset(int $themeID, string $assetKey, string $data): array {
        $flat = flattenArray('^|^', json_decode($this->getAssetData($themeID, $assetKey, $data), true));
        $assetData = flattenArray('^|^', json_decode($data, true));
        foreach ($assetData as $key => $val) {
            $flat[$key] = $val;
        }
        $data = json_encode(unflattenArray('^|^', $flat));
        $asset = $this->themeAssetModel->setAsset($themeID, $assetKey, $data);
        return [$assetKey => $this->generateAsset($assetKey, $asset['data'])];
    }

    /**
     * @inheritdoc
     */
    public function getAssetData($themeKey, string $assetKey): string {
        try {
            $asset = $this->themeAssetModel->getAsset($themeKey, $assetKey);
            $content = $asset['data'];
        } catch (NoResultsException $e) {
            $content = \Vanilla\Models\ThemeModel::ASSET_LIST[$assetKey]['default'] ?? '';
            if ($assetKey === 'variables') {
                $content = $this->addAddonVariables($content);
            }
        }
        return $content;
    }

    /**
     * @inheritdoc
     */
    public function deleteAsset($themeKey, string $assetKey) {
        $this->themeAssetModel->deleteAsset($themeKey, $assetKey);
    }

    /**
     * Normalize theme with assets response
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

        $res["assets"] = $this->getDefaultAssets($theme);
        $primaryAssets = array_intersect_key(
            $assets,
            array_flip(["fonts", "footer", "header", "scripts", "variables", "styles", "javascript"])
        );

        foreach ($primaryAssets as $assetKey => $asset) {
            $res["assets"][$assetKey] = $this->generateAsset($assetKey, $asset, $theme);
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

    /**
     * Get default assets
     *
     * @param array $theme
     * @return array
     */
    public function getDefaultAssets(array $theme): array {
        $assets = [];
        foreach (\Vanilla\Models\ThemeModel::ASSET_LIST as $assetKey => $assetDefinition) {
            if ($assetKey === 'variables') {
                $assets[$assetKey] =  $this->generateAsset($assetKey, $this->addAddonVariables($assetDefinition['default']));
            } else {
                $assets[$assetKey] =  $this->generateAsset($assetKey, $assetDefinition['default']);
            }
        }

        $assets['styles'] = $this->request->url('/api/v2/custom-theme/'.$theme['themeID'].'/styles.css', true);
        $assets['javascript'] = $this->request->url('/api/v2/custom-theme/'.$theme['themeID'].'/javascript.js', true);
        return $assets;
    }

    /**
     * Generate an asset object, given an asset array.
     *
     * @param string $key
     * @param string $data
     * @return Asset
     */
    private function generateAsset(string $key, string $data): ?Asset {
        $type = \Vanilla\Models\ThemeModel::ASSET_LIST[$key]["type"];

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
                return new JavascriptAsset($data);
            case "css":
                return new StyleAsset($data);
            default:
                return null;
        }
    }
}
