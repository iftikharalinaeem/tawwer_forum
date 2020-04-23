<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\ThemingApi\Models;

use Gdn_Session;
use Vanilla\Database\Operation\CurrentUserFieldProcessor;
use Vanilla\Database\Operation\CurrentDateFieldProcessor;
use Vanilla\Models\PipelineModel;

/**
 * Handle theme revisions.
 */
class ThemeRevisionModel extends PipelineModel {

    /** @var Gdn_Session */
    private $session;

    /**
     * ThemeRevisionModel constructor.
     *
     * @param Gdn_Session $session
     */
    public function __construct(Gdn_Session $session) {
        parent::__construct("themeRevision");
        $this->session = $session;

        $dateProcessor = new CurrentDateFieldProcessor();
        $dateProcessor->setInsertFields(["dateInserted"]);
        $this->addPipelineProcessor($dateProcessor);

        $userProcessor = new CurrentUserFieldProcessor($this->session);
        $userProcessor->setInsertFields(["insertUserID"]);
        $this->addPipelineProcessor($userProcessor);
    }

    /**
     * Create new revision
     *
     * @param int $themeID
     * @param string $name
     * @return int
     * @throws \Exception
     */
    public function create(int $themeID, string $name = ''): int {
        if (empty($name)) {
            $result = $this->get(
                ['themeID' => $themeID],
                [
                    "orderFields" => "name",
                    "orderDirection" => "desc",
                    'limit' => 1,
                ]
            );
            $revision = reset($result);
            $name = ++$revision['name'];
        }
        $revisionID = $this->insert(['themeID' => $themeID, 'name' => $name]);
        return $revisionID;
    }
}
