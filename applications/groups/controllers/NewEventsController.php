<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

use Vanilla\Forum\Navigation\ForumCategoryRecordType;

/**
 * New events controller.
 */
class NewEventsController extends AbstractEventsController {

    /**
     * Main handler for routing.
     *
     * /events/5-my-event
     * /events/group/5-group-name
     * /event/category/5-category-name
     *
     * @param string|null $parentRecordTypeOrID
     * @param string|null $parentRecordID
     */
    public function index(?string $parentRecordTypeOrID = null, ?string $parentRecordID = null) {
        if ($parentRecordID === null) {
            $this->renderSingleEvent($parentRecordTypeOrID);
        } else {
            $this->renderEventsList($parentRecordTypeOrID, $parentRecordID);
        }
    }

    /**
     * Render the events list page.
     *
     * @param string|null $parentRecordType
     * @param string|null $parentRecordID
     */
    private function renderEventsList(?string $parentRecordType, ?string $parentRecordID) {
        Gdn_Theme::section('NewEventList');

        [$parentRecordType, $parentRecordID] = $this->validateParentRecords($parentRecordType ?: 'category', $parentRecordID ?? -1);
        $this->canonicalUrl($this->eventModel->eventParentUrl($parentRecordType, $parentRecordID));

        // Make sure category banner works.
        if ($parentRecordType === ForumCategoryRecordType::TYPE) {
            $this->setData('CategoryID', $parentRecordID);
            $this->setData('ContextualCategoryID', $parentRecordID);
        }

        $newEventModule = new NewEventModule();
        $newEventModule->parentRecordID = $parentRecordID;
        $newEventModule->parentRecordType = $parentRecordType;
        $this->addModule($newEventModule, 'Panel');
        $this->addModule(new DiscussionFilterModule($this));

        $this->title(t('Events'));
        $this->applyBreadcrumbs($parentRecordType, $parentRecordID);
        $this->addBreadcrumb(t('Events'), $this->canonicalUrl());
        $this->render('index');
    }


    /**
     * Render a single event.
     *
     * @param int $eventID
     */
    private function renderSingleEvent(int $eventID) {
        $event = $this->eventsApi->get($eventID, []);
        $this->setIsReactView(true);
        $this->title($event['name']);
        $this->applyBreadcrumbs(EventRecordType::TYPE, $eventID);
        $this->render('event');
    }
}
