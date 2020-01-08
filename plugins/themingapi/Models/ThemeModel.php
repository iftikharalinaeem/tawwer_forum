<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\ThemingApi\Models;

use Garden\Web\Exception\ClientException;
use Gdn_Session;
use Vanilla\Database\Operation\CurrentUserFieldProcessor;
use Vanilla\Database\Operation\CurrentDateFieldProcessor;
use Vanilla\Theme\VariablesProviderInterface;
use Vanilla\Models\PipelineModel;

/**
 * Handle custom themes.
 */
class ThemeModel extends PipelineModel {

    /** @var Gdn_Session */
    private $session;

    /**
     * ThemeModel constructor.
     *
     * @param Gdn_Session $session
     */
    public function __construct(Gdn_Session $session) {
        parent::__construct("theme");
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
     * Set current theme.
     *
     * @param int $themeID Theme ID to set current.
     * @return array
     */
    public function setCurrentTheme(int $themeID): array {
        //check if theme exists
        try {
            $theme = $this->selectSingle(['themeID' => $themeID]);
        } catch (NoResultsException $e) {
            throw new NotFoundException('Theme with ID: ' . $themeID . ' not found!');
        }

        $this->update(['current' => 1], ['themeID' => $themeID]);
        $this->update(['current' => 0], ['current' => 1, 'themeID <>' => $themeID]);

        $theme = $this->selectSingle(['themeID' => $themeID], ['select' => ['themeID', 'name', 'parentTheme', 'current', 'dateUpdated']]);
        return $theme;
    }

    /**
     * Reset current DB theme when file based theme is activated.
     */
    public function resetCurrentTheme() {
        $this->update(['current' => 0], ['current' => 1]);
    }
}
