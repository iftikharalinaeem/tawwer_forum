<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

use Vanilla\Contracts\RecordInterface;
use Vanilla\Navigation\Breadcrumb;
use Vanilla\Navigation\BreadcrumbProviderInterface;

/**
 * Breadcrumb provider for events.
 */
class EventsBreadCrumbProvider implements BreadcrumbProviderInterface {
    /** @var EventModel $eventModel */
    private $eventModel;

    /** @var CategoryCollection */
    private $categoryCollection;

    /** @var GroupModel $groupModel */
    private $groupModel;

    /**
     * Constructor for EventBreadCrumbProvider.
     *
     * @param EventModel $eventModel
     * @param CategoryCollection $categoryCollection
     * @param GroupModel $groupModel
     */
    public function __construct(
        EventModel $eventModel,
        CategoryCollection $categoryCollection,
        GroupModel $groupModel
    ) {
        $this->eventModel = $eventModel;
        $this->categoryCollection = $categoryCollection;
        $this->groupModel = $groupModel;
    }

    /**
     * @inheritdoc
     */
    public function getForRecord(RecordInterface $record, string $locale = null): array {
        $event = $this->eventModel->getID($record->getRecordID(), DATASET_TYPE_ARRAY);
        $parentRecordType = $event['ParentRecordType'] ?? null;
        $parentRecordID = $event['ParentRecordID'] ?? null;

        $crumbs = [
            new Breadcrumb(t('Home'), \Gdn::request()->url('/', true)),
        ];

        if ($parentRecordType === EventModel::PARENT_TYPE_GROUP && $parentRecordID) {
            $crumbs = $this->calcGroupTypeBreadCrumbs($parentRecordID, $crumbs);
        }

        if ($parentRecordType === EventModel::PARENT_TYPE_CATEGORY && $parentRecordID) {
            $crumbs = $this->calcCategoryTypeBreadcrumbs($parentRecordID, $crumbs);
        }

        $eventName = $event['Name'] ?? '';
        $crumbs[] = new Breadcrumb(t($eventName), eventUrl($event));

        return $crumbs;
    }

    /**
     * @inheritdoc
     */
    public static function getValidRecordTypes(): array {
        return [EventRecordType::TYPE];
    }

    /**
     * Set Breadcrumbs for Group Event.
     *
     * @param int $parentRecordID
     * @param array $crumbs
     * @return array
     */
    private function calcGroupTypeBreadCrumbs(int $parentRecordID, array $crumbs): array {
        $crumbs[] = new Breadcrumb(t('Groups'), url('/groups'));
        $group = $this->groupModel->getID($parentRecordID);
        if ($group) {
            $groupName = $group['Name'] ?? '';
            $crumbs[] = new Breadcrumb(t($groupName), groupUrl($group));
        }

        return $crumbs;
    }

    /**
     * Set BreadCrumb for Category Event.
     *
     * @param int $parentRecordID
     * @param array $crumbs
     * @return array
     */
    private function calcCategoryTypeBreadcrumbs(int $parentRecordID, array $crumbs): array {
        $ancestors = $this->categoryCollection->getAncestors($parentRecordID);
        if ($ancestors) {
            foreach ($ancestors as $ancestor) {
                $crumbs[] = new Breadcrumb($ancestor['Name'], categoryUrl($ancestor));
            }
        }
        return $crumbs;
    }
}
