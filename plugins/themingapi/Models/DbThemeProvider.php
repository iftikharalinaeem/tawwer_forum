<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\ThemingApi;

use Gdn_Upload;
use Vanilla\Addon;
use Vanilla\Theme\ThemeProviderInterface;
use Vanilla\Models\ThemeModel;
use Vanilla\ThemingApi\Models\ThemeModel as ThemingModel;
use Vanilla\ThemingApi\Models\ThemeAssetModel;
use Vanilla\Exception\Database\NoResultsException;
use Garden\Web\Exception\NotFoundException;
use Gdn_Request;
use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\Models\FsThemeProvider;
use Vanilla\Theme\Asset;
use Vanilla\Theme\FontsAsset;
use Vanilla\Theme\HtmlAsset;
use Vanilla\Theme\JsonAsset;
use Vanilla\Theme\StyleAsset;
use Vanilla\Theme\ScriptsAsset;
use Vanilla\Theme\JavascriptAsset;
use Vanilla\Theme\ImageAsset;
use Vanilla\Models\ThemeModelHelper;

/**
 * Class DbThemeProvider
 */
class DbThemeProvider implements ThemeProviderInterface {

    const SELECT_FIELDS = ['themeID', 'parentTheme', 'name', 'current', 'dateUpdated', 'dateInserted'];

    /**
     * @var ThemeAssetModel
     */
    private $themeAssetModel;

    /**
     * @var ThemingModel
     */
    private $themeModel;

    /** @var ThemeModelHelper */
    private $themeHelper;

    /** @var ConfigurationInterface */
    private $config;

    /** @var Gdn_Request */
    private $request;

    /** @var FsThemeProvider */
    private $fsThemeProvider;

    /**
     * DbThemeProvider constructor.
     *
     * @param ThemeAssetModel $themeAssetModel
     * @param ThemingModel $themeModel
     * @param ConfigurationInterface $config
     * @param Gdn_Request $request
     * @param ThemeModelHelper $themeHelper
     * @param FsThemeProvider $fsThemeProvider
     */
    public function __construct(
        ThemeAssetModel $themeAssetModel,
        ThemingModel $themeModel,
        ConfigurationInterface $config,
        Gdn_Request $request,
        ThemeModelHelper $themeHelper,
        FsThemeProvider $fsThemeProvider
    ) {
        $this->themeAssetModel = $themeAssetModel;
        $this->themeModel = $themeModel;
        $this->themeHelper = $themeHelper;
        $this->config = $config;
        $this->request = $request;
        $this->fsThemeProvider = $fsThemeProvider;
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
                $this->themeModel->selectSingle(
                    ['themeID' => $themeKey],
                    ['select' => self::SELECT_FIELDS]
                ),
                $this->themeAssetModel->get(['themeID' => $themeKey], ['select' => ['assetKey', 'data']])
            );
        } catch (NoResultsException $e) {
            throw new NotFoundException('Theme with ID: ' . $themeKey . ' not found!');
        }

        return $theme;
    }

    /**
     * Check if a theme exists.
     *
     * @param string| int $themeKey
     * @return bool
     */
    public function themeExists($themeKey): bool {
        $themeExists = true;
        try {
            $this->themeModel->selectSingle(['themeID' => $themeKey]);
        } catch (NoResultsException $e) {
            $themeExists = false;
        }
        return $themeExists;
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
        $theme = $this->themeModel->selectSingle(['themeID' => $themeID], ['select' => self::SELECT_FIELDS]);

        $assets = $body['assets'] ?? [];
        foreach ($assets as $assetKey => $assetData) {
                $data = $assetData['data'] ?? $assetData;
                $this->setAsset($themeID, $assetKey, $data);
        }

        $themeAssets = $this->themeAssetModel->getLatestByThemeID($themeID);
        return $this->normalizeTheme($theme, $themeAssets);
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

        $assets = $body['assets'] ?? [];
        foreach ($assets as $assetKey => $assetData) {
            $data = $assetData['data'] ?? $assetData;
            $this->setAsset($themeID, $assetKey, $data);
        }

        $theme = $this->themeModel->selectSingle(
            ['themeID' => $themeID],
            ['select' => self::SELECT_FIELDS]
        );
        $themeAssets = $this->themeAssetModel->getLatestByThemeID($themeID);
        return $this->normalizeTheme(
            $theme,
            $themeAssets
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
    public function setCurrent($themeID): array {
        try {
            $theme = $this->normalizeTheme(
                $this->themeModel->setCurrentTheme($themeID),
                $this->themeAssetModel->get(['themeID' => $themeID], ['select' => ['assetKey', 'data']])
            );

            if (!empty($theme['parentTheme'])) {
                $this->config->set('Garden.Theme', $theme['parentTheme']);
                $this->config->set('Garden.MobileTheme', $theme['parentTheme']);
            }
            $this->config->set('Garden.CurrentTheme', $themeID);
        } catch (NoResultsException $e) {
            throw new NotFoundException('Theme with ID: ' . $themeID . ' not found!');
        }
        return $theme;
    }

    /**
     * Reset current db theme when file based theme activated
     */
    public function resetCurrent() {
        $this->themeModel->resetCurrentTheme();
    }

    /**
     * @inheritdoc
     */
    public function setPreviewTheme($themeID): array {
        $this->themeHelper->setSessionPreviewTheme($themeID, $this);
        if (!empty($themeID)) {
            $theme = $this->getThemeWithAssets($themeID);
        } else {
            $theme = $this->getCurrent();
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
        $flat = flattenArray('^|^', json_decode($this->getAssetData($themeID, $assetKey), true));
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
            $content = ThemeModel::ASSET_LIST[$assetKey]['default'] ?? '';
            if ($assetKey === ThemeModel::JAVASCRIPT) {
                // when some asset is not defined on DB level yet
                // lets check if parent theme template has it implemented and substitute it
                // if we need to reset parent asset empty asset should be posted
                $content = $this->getParentAssetData($themeKey, $assetKey);
            }
        }
        return $content;
    }

    /**
     * Get parent theme asset data
     *
     * @param int $themeID Theme id
     * @param string $assetKey Asset key
     * @return string Asset data (content)
     */
    private function getParentAssetData(int $themeID, string $assetKey): string {
        $content = '';
        $theme = $this->themeModel->selectSingle(['themeID' => $themeID]);
        if (!empty($theme['parentTheme'])) {
            $content = $this->fsThemeProvider->getAssetData($theme['parentTheme'], $assetKey);
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
     * @inheritdoc
     */
    public function getMasterThemeKey($themeID): string {
        try {
            $theme = $this->themeModel->selectSingle(['themeID' => $themeID], ['select' => ['themeID', 'parentTheme']]);
        } catch (NoResultsException $e) {
            $theme['parentTheme'] = ThemeModel::FALLBACK_THEME_KEY;
        }
        $parentTheme = $this->fsThemeProvider->getThemeAddon($theme['parentTheme']);
        return $parentTheme->getKey();
    }

    /**
     * @inheritdoc
     */
    public function getName($themeID): string {
        $theme = $this->themeModel->selectSingle(['themeID' => $themeID], ['select' => ['themeID', 'name']]);
        return $theme['name'];
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
            'parentTheme' => $theme['parentTheme'] ?? null,
            'current' => $theme['current'],
            'type' => 'themeDB',
            'version' => $theme['version'] ?? crc32($theme['dateUpdated']->format('Y-m-d H:i:s'))
        ];

        $res["assets"] = $this->getDefaultAssets($theme);
        $primaryAssets = array_intersect_key(
            $assets,
            array_flip(array_keys(ThemeModel::ASSET_LIST))
        );

        foreach ($primaryAssets as $assetKey => $asset) {
            if ($asset !== null) {
                $res["assets"][$assetKey] = $this->generateAsset($assetKey, $asset);
            } else {
                unset($res['assets'][$assetKey]);
            }
        }

        // Fix these linked assets to be links.
        if ($res['assets'][ThemeModel::STYLES] ?? false) {
            $res["assets"][ThemeModel::STYLES] = $this->request->getSimpleUrl('/api/v2/themes/'.$theme['themeID'].'/assets/styles.css');
        }

        if ($res["assets"][ThemeModel::JAVASCRIPT] ?? false) {
            $res["assets"][ThemeModel::JAVASCRIPT] = $this->request->getSimpleUrl('/api/v2/themes/'.$theme['themeID'].'/assets/javascript.js');
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

        $parentTheme = isset($theme['parentTheme']) ? $this->fsThemeProvider->getThemeAddon($theme['parentTheme']) : null;
        if (!($parentTheme instanceof Addon)) {
            $res['preview']['info']['Warning'] = ['type' => 'string', 'info' => 'Parent theme ('.$theme['parentTheme'].') is not valid'];
        } else {
            $res['preview']['info']['Template'] = ['type' => 'string', 'info' => $parentTheme->getInfoValue('name')];
        }
        $res['preview']['info']['Created'] = ['type' => 'date', 'info' => $theme['dateInserted']->format('Y-m-d H:i:s')];
        $res['preview']['info']['Updated'] = ['type' => 'date', 'info' => $theme['dateUpdated']->format('Y-m-d H:i:s')];

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
            $assets[$assetKey] =  $this->generateAsset($assetKey, $assetDefinition['default']);
        }

        return $assets;
    }

    /**
     * Generate an asset object, given an asset array.
     *
     * @param string $key
     * @param string $data
     * @return Asset
     */
    private function generateAsset(string $key, ?string $data): ?Asset {
        if (!$data) {
            return null;
        }
        $type = ThemeModel::ASSET_LIST[$key]["type"];

        switch ($type) {
            case "html":
                return new HtmlAsset($data);
            case "json":
                if ($key === ThemeModel::FONTS) {
                    return new FontsAsset(json_decode($data, true));
                } elseif ($key === ThemeModel::SCRIPTS) {
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
