<?php
/**
 * @copyright 2008-2016 Vanilla Forums, Inc.
 * @license Proprietary
 */

use Garden\Web\Exception\NotFoundException;
use Vanilla\Forum\Navigation\ForumCategoryRecordType;
use Vanilla\Forum\Navigation\GroupRecordType;
use Vanilla\Groups\Models\EventPermissions;
use Vanilla\Models\GenericRecord;
use Vanilla\Navigation\BreadcrumbModel;

/**
 * Groups Application - Events Controller
 *
 * @author Tim Gunter <tim@vanillaforums.com>
 * @package groups
 * @since 1.0
 */
class EventsController extends Gdn_Controller {

    const ALLOWED_PARENT_RECORD_TYPES = [ForumCategoryRecordType::TYPE, GroupRecordType::TYPE];

    /** @var EventModel */
    private $eventModel;

    /** @var BreadcrumbModel */
    private $breadcrumbModel;

    /** @var GroupModel */
    private $groupModel;

    /**
     * DI.
     *
     * @param EventModel $eventModel
     * @param BreadcrumbModel $breadcrumbModel
     * @param GroupModel $groupModel
     */
    public function __construct(EventModel $eventModel, BreadcrumbModel $breadcrumbModel, GroupModel $groupModel) {
        parent::__construct();
        $this->eventModel = $eventModel;
        $this->breadcrumbModel = $breadcrumbModel;
        $this->groupModel = $groupModel;
    }


    /**
     * Include JS, CSS, and modules used by all methods.
     *
     * Always called by dispatcher before controller's requested method.
     */
    public function initialize() {
        // Set up head
        $this->Head = new HeadModule($this);
        $this->addJsFile('jquery.js');
        $this->addJsFile('jquery.livequery.js');
        $this->addJsFile('jquery-ui.min.js');
        $this->addJsFile('jquery.form.js');
        $this->addJsFile('jquery.popup.js');
        $this->addJsFile('jquery.gardenhandleajaxform.js');
        $this->addJsFile('global.js');
        $this->addJsFile('event.js');
        $this->addCssFile('vanillicon.css', 'static');
        $this->addCssFile('style.css');
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
        ///
        /// Validation and permission logic.
        ///
        $this->permission('Garden.SignIn.Allow');

        if ($parentRecordType === null) {
            throw new NotFoundException();
        }
        $parentRecordType ? strtolower($parentRecordType) : null;
        if (!in_array($parentRecordType, self::ALLOWED_PARENT_RECORD_TYPES)) {
            throw new NotFoundException();
        }

        if ($parentRecordID === null) {
            throw new NotFoundException();
        }

        // We have a slug on this id.
        $parentRecordID = GroupModel::idFromSlug($parentRecordID);

        // Validate our permissions for the records.
        $this->eventModel->checkParentEventPermission(EventPermissions::VIEW, $parentRecordType, $parentRecordID);

        ///
        /// Prepare data for view rendering.
        ///
        $this->title(t('Events'));

        // Set breadcrumbs.
        $crumbs = $this->breadcrumbModel->getForRecord(new GenericRecord($parentRecordType, $parentRecordID));
        // Legacy page so pop off the first crumb.
        array_shift($crumbs);

        foreach ($crumbs as $crumb) {
            $this->addBreadcrumb($crumb->getName(), $crumb->getUrl());
        }

        $this->addBreadcrumb(t('Events'), $this->canonicalUrl());
        $this->CssClass .= ' NoPanel';

        $eventCriteria = [
            'ParentRecordID' => $parentRecordID,
            'ParentRecordType' => $parentRecordType,
        ];
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
