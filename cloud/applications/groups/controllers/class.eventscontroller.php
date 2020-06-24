<?php
/**
 * @copyright 2008-2016 Vanilla Forums, Inc.
 * @license Proprietary
 */

use Garden\Web\Exception\NotFoundException;
use Vanilla\Forum\Navigation\ForumCategoryRecordType;
use Vanilla\Forum\Navigation\GroupRecordType;
use Vanilla\Navigation\BreadcrumbModel;

/**
 * Groups Application - Events Controller
 *Ø
 * @author Tim Gunter <tim@vanillaforums.com>
 * @package groups
 * @since 1.0
 */
class EventsController extends AbstractEventsController {

    /** @var GroupModel */
    private $groupModel;

    /**
     * DI.
     *
     * @param GroupModel $groupModel
     */
    public function __construct(GroupModel $groupModel) {
        parent::__construct();
        $this->groupModel = $groupModel;
    }

    /**
     * Include JS, CSS, and modules used by all methods.
     *
     * Always called by dispatcher before controller's requested method.
     */
    public function initialize() {
        parent::initialize();
        $this->addJsFile('jquery.livequery.js');
        $this->addJsFile('jquery-ui.min.js');
        $this->addJsFile('jquery.form.js');
        $this->addJsFile('jquery.popup.js');
        $this->addJsFile('jquery.gardenhandleajaxform.js');
        $this->addJsFile('event.js');
        Gdn_Theme::section('Events');

        parent::initialize();
    }

    /**
     * Event list page.
     *
     * @param string|null $parentRecordType The parent record type.
     * @param string|null $parentRecordID The parent record ID. May have a slug appended.
     */
    public function index(?string $parentRecordType = null, ?string $parentRecordID = null) {
        [$parentRecordType, $parentRecordID] = $this->validateParentRecords($parentRecordType, $parentRecordID);

        $this->title(t('Events'));
        $this->applyBreadcrumbs($parentRecordType, $parentRecordID);

        $this->CssClass .= ' NoPanel';

        $eventCriteria = [
            'ParentRecordType' => $parentRecordType,
            'ParentRecordID' => $parentRecordID,
        ];

        if ($parentRecordType === GroupRecordType::TYPE) {
            $this->applyGroupSpecificViewData($parentRecordID);
        } elseif ($parentRecordType === ForumCategoryRecordType::TYPE) {
            $parentIDs = [-1, $parentRecordID];
            // get all parent category IDs up to the root.
            $ancestors = CategoryModel::getAncestors($parentRecordID, true);
            if ($ancestors) {
                $parentIDs = array_unique(array_merge($parentIDs, array_column($ancestors, 'CategoryID')));
            }
            $eventCriteria['ParentRecordID'] = $parentIDs;
        }

        // Upcoming events
        $upcomingRange = c('Groups.Events.UpcomingRange', '+365 days');
        $upcomingEvents = $this->eventModel->getUpcoming($upcomingRange, $eventCriteria);

        // Recent events
        $recentRange = c('Groups.Events.RecentRange', '-365 days');
        $recentEvents = $this->eventModel->getUpcoming($recentRange, $eventCriteria, true);

        $this->EventArguments['UpcomingEvents'] = &$upcomingEvents;
        $this->EventArguments['RecentEvents'] = &$recentEvents;
        $this->fireEvent('EventsLoaded');

        $this->setData('UpcomingEvents', $upcomingEvents);
        $this->setData('RecentEvents', $recentEvents);

        $this->fetchView('event_functions', 'event', 'groups');
        $this->fetchView('group_functions', 'group', 'groups');

        $this->RequestMethod = 'events';
        $this->View = 'events';
        $this->render();
    }

    /**
     * We have some group specific view data.
     *
     * @param int $groupID
     */
    private function applyGroupSpecificViewData(int $groupID) {
        $group = $this->groupModel->getID($groupID, DATASET_TYPE_ARRAY);
        if (!$group) {
            throw new NotFoundException('Group');
        }

        $this->EventArguments['Group'] = &$group;
        $this->fireEvent('GroupLoaded');

        $this->setData('Group', $group);
        $this->setData('NewButtonId', val('GroupID', $group));
    }
}
