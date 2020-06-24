<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
namespace Vanilla\Badges\Menu;

use Vanilla\Menu\CounterProviderInterface;
use Vanilla\Menu\Counter;

/**
 * Menu counter provider for badges model.
 */
class BadgesCounterProvider implements CounterProviderInterface {

    /** @var \UserBadgeModel */
    private $badgeModel;

    /** @var \Gdn_Session */
    private $session;

    /**
     * @param \UserBadgeModel $badgeModel
     * @param \Gdn_Session $session
     */
    public function __construct(
        \UserBadgeModel $badgeModel,
        \Gdn_Session $session
    ) {
        $this->badgeModel = $badgeModel;
        $this->session = $session;
    }

    /**
     * @inheritdoc
     */
    public function getMenuCounters(): array {
        $counters[] = new Counter("BadgeRequests", $this->badgeModel->getBadgeRequestCount() ?? 0);
        return $counters;
    }
}
