<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\ThemingApi;

use Vanilla\Addon;
use Vanilla\Theme\Asset\ThemeAsset;
use Vanilla\Theme\Theme;
use Vanilla\Theme\ThemeProviderCleanupInterface;
use Vanilla\Theme\ThemeProviderInterface;
use Vanilla\Theme\ThemeService;
use Vanilla\Theme\ThemeProviderWriteInterface;
use Vanilla\ThemingApi\Models\ThemeModel as ThemingModel;
use Vanilla\ThemingApi\Models\ThemeAssetModel;
use Vanilla\ThemingApi\Models\ThemeRevisionModel;
use Vanilla\Exception\Database\NoResultsException;
use Garden\Web\Exception\NotFoundException;
use Gdn_Request;
use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\Theme\FsThemeProvider;
use Vanilla\Theme\Asset;
use Vanilla\Theme\ThemeServiceHelper;
use UserModel;

/**
 * Class DbThemeProvider
 */
class DbThemeProvider implements ThemeProviderInterface, ThemeProviderCleanupInterface, ThemeProviderWriteInterface {

    const SELECT_FIELDS = ['themeID', 'parentTheme', 'name', 'current', 'dateUpdated', 'dateInserted', 'revisionID'];

    /**
     * @var ThemeAssetModel
     */
    private $themeAssetModel;

    /**
     * @var ThemingModel
     */
    private $themeModel;

    /**
     * @var ThemeRevisionModel $themeRevisionModel
     */
    private $themeRevisionModel;

    /** @var ThemeServiceHelper */
    private $themeHelper;

    /** @var ConfigurationInterface */
    private $config;

    /** @var Gdn_Request */
    private $request;

    /** @var FsThemeProvider */
    private $fsThemeProvider;

    /** @var UserModel $userModel */
    private $userModel;

    /**
     * DbThemeProvider constructor.
     *
     * @param ThemeAssetModel $themeAssetModel
     * @param ThemingModel $themeModel
     * @param ConfigurationInterface $config
     * @param Gdn_Request $request
     * @param ThemeServiceHelper $themeHelper
     * @param FsThemeProvider $fsThemeProvider
     * @param ThemeRevisionModel $themeRevisionModel
     * @param UserModel $userModel
     */
    public function __construct(
        ThemeAssetModel $themeAssetModel,
        ThemingModel $themeModel,
        ConfigurationInterface $config,
        Gdn_Request $request,
        ThemeServiceHelper $themeHelper,
        FsThemeProvider $fsThemeProvider,
        ThemeRevisionModel $themeRevisionModel,
        UserModel $userModel
    ) {
        $this->themeAssetModel = $themeAssetModel;
        $this->themeModel = $themeModel;
        $this->themeHelper = $themeHelper;
        $this->config = $config;
        $this->request = $request;
        $this->fsThemeProvider = $fsThemeProvider;
        $this->themeRevisionModel = $themeRevisionModel;
        $this->userModel = $userModel;
    }

    /**
     * @inheritdoc
     */
    public function handlesThemeID($themeID): bool {
        return is_numeric($themeID);
    }

    /**
     * @inheritdoc
     */
    public function getTheme($themeKey, array $args = []): Theme {
        try {
            $theme = $this->themeModel->selectSingle(
                ['themeID' => $themeKey],
                ['select' => self::SELECT_FIELDS]
            );
            if (isset($args['revisionID'])) {
                $theme['revisionID'] = $args['revisionID'];
            }
            $theme['revisionName'] = $this->themeRevisionModel->getName($theme['revisionID']);
            $assets = $this->themeAssetModel->get(
                [
                    'themeID' => $themeKey,
                    'revisionID' => $theme['revisionID'],
                ],
                ['select' => ['assetKey', 'data']]
            );

            return $this->createTheme($theme, $assets);
        } catch (NoResultsException $e) {
            throw new NotFoundException('Theme');
        }
    }

    /**
     * @inheritdoc
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
     * @inheritdoc
     */
    public function getAllThemes(): array {
        $dbThemes = $this->themeModel->get();

        $allDbThemes = [];
        foreach ($dbThemes as $dbTheme) {
            $allDbThemes[] = $this->getTheme($dbTheme["themeID"]);
        }
        return $allDbThemes;
    }

    /**
     * @inheritdoc
     */
    public function getThemeRevisions($themeID): array {
        $revisions = $this->themeModel->getRevisions($themeID);
        $this->userModel->expandUsers(
            $revisions,
            ["insertUserID"]
        );
        foreach ($revisions as &$revision) {
            $revision = $this->createTheme(
                $revision,
                $this->themeAssetModel->get(
                    [
                        'themeID' => $themeID,
                        'revisionID' => $revision['revisionID'],
                    ],
                    ['select' => ['assetKey', 'data']]
                )
            );
        }
        return $revisions;
    }

    /**
     * @inheritdoc
     */
    public function setCurrentTheme($themeID): Theme {
        try {
            $theme = $this->getTheme($themeID);
            $this->themeModel->setCurrentTheme($themeID);
            $parentID = $theme->getParentTheme() ?? ThemeService::FOUNDATION_THEME_KEY;
            $this->config->set('Garden.Theme', $parentID);
            $this->config->set('Garden.MobileTheme', $parentID);
            $this->config->set('Garden.CurrentTheme', $themeID);

            return $theme;
        } catch (NoResultsException $e) {
            throw new NotFoundException('Theme');
        }
    }

    /**
     * @inheritdoc
     */
    public function getMasterThemeKey($themeID): string {
        try {
            $theme = $this->themeModel->selectSingle(['themeID' => $themeID], ['select' => ['themeID', 'parentTheme']]);
        } catch (NoResultsException $e) {
            $theme['parentTheme'] = ThemeService::FALLBACK_THEME_KEY;
        }
        $parentTheme = $this->fsThemeProvider->getThemeAddon($theme['parentTheme']);
        return $parentTheme->getKey();
    }

    /**
     * @inheritdoc
     */
    public function setPreviewTheme($themeID, int $revisionID = null): Theme {
        $theme = $this->getTheme($themeID, ['revisionID' => $revisionID]);
        $this->themeHelper->setSessionPreviewTheme($theme);
        return $theme;
    }

    ///
    /// ThemeProviderCleanupInterface
    ///

    /**
     * @inheritdoc
     */
    public function afterCurrentProviderChange(): void {
        $this->themeModel->resetCurrentTheme();
    }

    ///
    /// ThemeProviderWriteInterface
    ///

    /**
     * @inheritdoc
     */
    public function setAsset($themeID, string $assetKey, string $content): ThemeAsset {
        $this->patchTheme($themeID, [
            'assets' => [
                $assetKey => [
                    'data' => $content,
                ]
            ],
        ]);

        // Updated asset.
        $updatedAsset = $this->getTheme($themeID)->getAssets()[$assetKey];
        return $updatedAsset;
    }

    /**
     * @inheritdoc
     */
    public function sparseUpdateAsset($themeID, string $assetKey, string $data): Asset\ThemeAsset {
        $existingAsset = $this->getTheme($themeID)->getAssets()[$assetKey] ?? null;
        if ($existingAsset instanceof Asset\JsonThemeAsset) {
            // Merge the 2 assets together.
            $existingData = $existingAsset->getValue();
            $newData = json_decode($data, true);
            $data = array_replace_recursive($existingData, $newData);
            $data = json_encode($data, JSON_UNESCAPED_UNICODE);
        }

        return $this->setAsset($themeID, $assetKey, $data);
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
    public function postTheme(array $body): Theme {
        $parentTheme = $body['parentTheme'];
        $parentTheme = $this->fsThemeProvider->getTheme($parentTheme);
        $body['revisionID'] = -1;
        $themeID = $this->themeModel->insert($body);
        $revisionID = $this->themeRevisionModel->insert([
            'themeID' => $themeID,
            'name' => 'rev 1',
        ]);
        $body['revisionID'] = $revisionID;
        $this->themeModel->update(
            ['revisionID' => $revisionID],
            ['themeID' => $themeID]
        );

        $assets = $body['assets'] ?? [];

        $insertedAssets = [];
        foreach ($assets as $assetKey => $assetData) {
            $data = $assetData['data'];
            $this->themeAssetModel->setAsset(
                $themeID,
                $revisionID,
                $assetKey,
                $data
            );
            $insertedAssets[] = $assetKey;
        }

        // Add in any missing assets from the previous revision.
        $this->copyForwardAssetsIntoRevision($parentTheme, $themeID, $revisionID, $insertedAssets);

        return $this->getTheme($themeID);
    }

    /**
     * @inheritdoc
     */
    public function patchTheme($themeID, array $body): Theme {
        // Get the existing theme.
        $existingTheme = $this->getTheme($themeID);

        if (isset($body['revisionID'])) {
            // We are restoring a revision.
            if (isset($body['revisionName'])) {
                $this->themeRevisionModel->update(['name' => $body['revisionName']], ['revisionID' => $body['revisionID']]);
            }
            $this->themeModel->update(['revisionID' => $body['revisionID']], ['themeID' => $themeID]);
            return $this->getTheme($themeID, ['revisionID' => $body['revisionID']]);
        }

        $body['revisionID'] = $this->themeRevisionModel->create($themeID, $body['revisionName'] ?? '');
        $this->themeModel->update($body, ['themeID' => $themeID]);

        $revisionID = $body["revisionID"];
        $assets = $body['assets'] ?? [];

        $insertedAssets = [];
        // Insert the new assets.
        foreach ($assets as $assetKey => $assetData) {
            $insertedAssets[] = $assetKey;
            $data = $assetData['data'] ?? $assetData;
            $this->themeAssetModel->setAsset($themeID, $revisionID, $assetKey, $data);
        }

        // Add in any missing assets from the previous revision.
        $this->copyForwardAssetsIntoRevision($existingTheme, $themeID, $revisionID, $insertedAssets);

        $theme = $this->themeModel->selectSingle(
            [
                'themeID' => $themeID,
                'revisionID' => $revisionID,
            ],
            ['select' => self::SELECT_FIELDS]
        );
        $theme['revisionName'] = $this->themeRevisionModel->getName($revisionID);

        return $this->getTheme($themeID);
    }

    /**
     * @inheritdoc
     */
    public function deleteTheme($themeID) {
        // check if theme exists
        try {
            $this->themeModel->selectSingle(['themeID' => $themeID]);
        } catch (NoResultsException $e) {
            throw new NotFoundException('Theme with ID: ' . $themeID . ' not found!');
        }
        $this->themeModel->delete(['themeID' => $themeID]);
    }

    ///
    /// Utilities
    ///

    /**
     * Copy assets out of an existing theme into a new theme revision.
     *
     * @param Theme $theme The existing theme.
     * @param int $insertThemeID The themeID to insert into.
     * @param int $insertRevisionID The revisionID to insert into.
     * @param array $alreadyInsertedAssets Asset names that have already been inserted. These will be skipped.
     */
    private function copyForwardAssetsIntoRevision(Theme $theme, int $insertThemeID, int $insertRevisionID, array $alreadyInsertedAssets) {
        /**
         * @var string $assetName
         * @var Asset\ThemeAsset $asset
         */
        foreach ($theme->getAssets() as $assetName => $asset) {
            if (in_array($assetName, $alreadyInsertedAssets)) {
                continue;
            }

            $this->themeAssetModel->setAsset($insertThemeID, $insertRevisionID, $assetName, $asset->__toString());
        }
    }

    /**
     * Combine a theme array and asset array into a theme data class.
     *
     * @param array $dbTheme
     * @param array $dbAssets
     * @return Theme
     */
    private function createTheme(array $dbTheme, array $dbAssets): Theme {
        $dbAssets = array_column($dbAssets, null, 'assetKey');

        $dbTheme['type'] = 'themeDB';
        $dbTheme['assets'] = $dbAssets;
        $dbTheme['version'] = crc32($dbTheme['dateUpdated']->format('Y-m-d H:i:s'));
        $theme = new Theme($dbTheme);

        $preview = $theme->getPreview();
        $parentAddon = isset($dbTheme) ? $this->fsThemeProvider->getThemeAddon($dbTheme['parentTheme']) : null;
        if (!($parentAddon instanceof Addon)) {
            $preview->addInfo('string', 'Warning', 'Parent theme ('.$dbTheme['parentTheme'].') is not valid');
            $theme->setAddon($this->fsThemeProvider->getThemeAddon(ThemeService::FALLBACK_THEME_KEY));
        } else {
            $theme->setAddon($parentAddon);
            $preview->addInfo('string', 'Template', $parentAddon->getName());
        }
        $preview->addInfo('date', 'Created', $dbTheme['dateInserted']->format('Y-m-d H:i:s'));
        $preview->addInfo('date', 'Updated', $dbTheme['dateUpdated']->format('Y-m-d H:i:s'));
        return $theme;
    }
}
