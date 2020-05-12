<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

use Garden\Web\Exception\NotFoundException;
use Vanilla\Forum\Navigation\ForumCategoryRecordType;
use Vanilla\Groups\Models\EventPermissions;
use Vanilla\Models\GenericRecord;

/**
 * New events controller.
 */
class NewEventsController extends AbstractEventsController {

    /**
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
        $events = $this->eventsApi->index([
            'parentRecordType' => $parentRecordType,
            'parentRecordID' => $parentRecordID,
        ]);

        $this->canonicalUrl($this->eventModel->eventParentUrl($parentRecordType, $parentRecordID));

        // Make sure category banner works.
        if ($parentRecordType === ForumCategoryRecordType::TYPE) {
            $this->setData('CategoryID', $parentRecordID);
            $this->setData('ContextualCategoryID', $parentRecordID);
        }

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
        $this->title($event['name']);
        $this->applyBreadcrumbs(EventRecordType::TYPE, $eventID);
        $this->render('index');
    }

}
