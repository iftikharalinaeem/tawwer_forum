<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

use Vanilla\Contracts\RecordInterface;
use Vanilla\Forum\Navigation\ForumCategoryRecordType;
use Vanilla\Forum\Navigation\GroupRecordType;
use Vanilla\Navigation\Breadcrumb;
use Vanilla\Navigation\BreadcrumbModel;
use Vanilla\Navigation\BreadcrumbProviderInterface;

/**
 * Breadcrumb provider for events.
 */
class EventsBreadcrumbProvider implements BreadcrumbProviderInterface {

    /** @var EventModel $eventModel */
    private $eventModel;

    /**
     * Constructor for EventBreadCrumbProvider.
     *
     * @param EventModel $eventModel
     */
    public function __construct(EventModel $eventModel) {
        $this->eventModel = $eventModel;
    }

    /**
     * @inheritdoc
     */
    public function getForRecord(RecordInterface $record, string $locale = null): array {
        $event = $this->eventModel->getID($record->getRecordID(), DATASET_TYPE_ARRAY);
        $parentRecordType = $event['ParentRecordType'] ?? null;
        $parentRecordID = $event['ParentRecordID'] ?? null;

        $breadCrumbModel = Gdn::getContainer()->get(BreadcrumbModel::class);

        if ($parentRecordType === EventModel::PARENT_TYPE_GROUP && $parentRecordID !== null) {
            $crumbs = $breadCrumbModel->getForRecord(new GroupRecordType($parentRecordID));
        }

        if ($parentRecordType === EventModel::PARENT_TYPE_CATEGORY && $parentRecordID !== null) {
            $crumbs = $breadCrumbModel->getForRecord(new ForumCategoryRecordType($parentRecordID));
        }

        $groupEventsCrumb = new Breadcrumb(
            t('Events'),
            $this->eventModel->eventParentUrl($parentRecordType, $parentRecordID)
        );
        $crumbs[] = $groupEventsCrumb;

        $eventName = $event['Name'] ?? '';
        $crumbs[] = new Breadcrumb(t($eventName), $this->eventModel->eventUrl($event));

        return $crumbs;
    }

    /**
     * @inheritdoc
     */
    public static function getValidRecordTypes(): array {
        return [EventRecordType::TYPE];
    }
}
