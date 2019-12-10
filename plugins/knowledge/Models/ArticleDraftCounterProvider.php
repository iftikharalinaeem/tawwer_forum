<?php
/**
 * @author Alexander Kim <alexander.k@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Knowledge\Models;

use Vanilla\Models\DraftModel;
use Vanilla\Menu\CounterProviderInterface;
use Vanilla\Menu\Counter;

/**
 * Menu counter provider for user.
 */
class ArticleDraftCounterProvider implements CounterProviderInterface {

    /** @var DraftModel $draftModel */
    private $draftModel;

    /** @var \Gdn_Session */
    private $session;

    /**
     * Initialize class with dependencies
     *
     * @param DraftModel $draftModel
     * @param \Gdn_Session $session
     */
    public function __construct(
        DraftModel $draftModel,
        \Gdn_Session $session
    ) {
        $this->draftModel = $draftModel;
        $this->session = $session;
    }

    /**
     * @inheritdoc
     */
    public function getMenuCounters(): array {
        $counters = [];
        $count= 0;
        $userID = $this->session->UserID ?? 0;
        if (!empty($userID)) {
            $count = $this->draftModel->draftsCount($userID);
        }
        $counters[] = new Counter("ArticleDrafts", $count);
        return $counters;
    }
}
