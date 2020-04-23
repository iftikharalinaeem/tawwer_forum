<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\ThemingApi\Models;

use Gdn_Session;
use Vanilla\Models\PipelineModel;
use Vanilla\Database\Operation\CurrentUserFieldProcessor;
use Vanilla\Database\Operation\CurrentDateFieldProcessor;
use Vanilla\Exception\Database\NoResultsException;

/**
 * Handle theme assets.
 */
class ThemeAssetModel extends PipelineModel {

    /** @var Gdn_Session */
    private $session;

    /**
     * ThemeAssetModel constructor.
     *
     * @param Gdn_Session $session
     */
    public function __construct(Gdn_Session $session) {
        parent::__construct("themeAsset");
        $this->session = $session;

        $dateProcessor = new CurrentDateFieldProcessor();
        $dateProcessor->setInsertFields(["dateInserted", "dateUpdated"])
            ->setUpdateFields(["dateUpdated"]);
        $this->addPipelineProcessor($dateProcessor);

        $userProcessor = new CurrentUserFieldProcessor($this->session);
        $userProcessor->setInsertFields(["insertUserID", "updateUserID"])
            ->setUpdateFields(["updateUserID"]);
        $this->addPipelineProcessor($userProcessor);
    }

    /**
     * Set asset. Insert asset record or update record if already exists.
     *
     * @param int $themeID
     * @param int $revisionID
     * @param string $assetKey
     * @param string $data
     * @return array
     */
    public function setAsset(int $themeID, int $revisionID, string $assetKey, string $data): array {
        $where = [
            'themeID' => $themeID,
            'revisionID' => $revisionID,
            'assetKey' => $assetKey
        ];
        try {
            $themeAsset = $this->selectSingle($where);
            // if asset exists -> update
            $this->update(
                ['data' => $data],
                $where
            );
        } catch (NoResultsException $e) {
            // if asset does not exist -> insert
            $this->insert([
                'data' => $data,
                'themeID' => $themeID,
                'revisionID' => $revisionID,
                'assetKey' => $assetKey
            ]);
        }
        return $this->selectSingle($where);
    }

    /**
     * Get asset by themeId and assetKey.
     *
     * @param int $themeID
     * @param int $revisionID
     * @param string $assetKey
     * @return array
     */
    public function getAsset(int $themeID, int $revisionID, string $assetKey): array {
        return $this->selectSingle(
            [
                'themeID' => $themeID,
                'revisionID' => $revisionID,
                'assetKey' => $assetKey
            ],
            ['select' => ['data']]
        );
    }

    /**
     * Delete asset
     *
     * @param int $themeID
     * @param string $assetKey
     * @return bool
     */
    public function deleteAsset(int $themeID, string $assetKey): bool {

        return $this->delete([
            'themeID' => $themeID,
            'assetKey' => $assetKey
        ]);
    }

    public function getLatestByThemeID(int $themeID) {
        $result = $this->sql()
            ->from($this->getTable().' a')
            ->select(['a.assetKey', 'a.data'])
            ->join($this->getTable().' l', 'a.themeID = l.themeID AND a.assetKey = l.assetKey AND a.dateUpdated < l.dateUpdated', 'left')
            ->where('a.themeID', $themeID)
            ->where(['l.themeID IS NULL' => ''])
            ->get()->resultArray()
        ;
        return $result;
    }
}
