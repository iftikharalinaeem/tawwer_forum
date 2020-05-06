<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

use Vanilla\Contracts\RecordInterface;
use Vanilla\Forum\Navigation\GroupRecordType;
use Vanilla\Navigation\Breadcrumb;
use Vanilla\Navigation\BreadcrumbProviderInterface;

/**
 * Breadcrumb provider for events.
 */
class GroupBreadcrumbProvider implements BreadcrumbProviderInterface {

    /** @var GroupModel $groupModel */
    private $groupModel;

    /**
     * Constructor for GroupBreadcrumbProvider.
     *
     * @param GroupModel $groupModel
     */
    public function __construct(GroupModel $groupModel) {
        $this->groupModel = $groupModel;
    }

    /**
     * @inheritdoc
     */
    public function getForRecord(RecordInterface $record, string $locale = null): array {
        $crumbs = [
            new Breadcrumb(t('Home'), \Gdn::request()->url('/', true)),
        ];

        $crumbs[] = new Breadcrumb(t('Groups'), url('/groups'));
        $group = $this->groupModel->getID($record->getRecordID());
        if ($group) {
            $groupName = $group['Name'] ?? '';
            $crumbs[] = new Breadcrumb(t($groupName), groupUrl($group));
        }

        return $crumbs;
    }

    /**
     * @inheritdoc
     */
    public static function getValidRecordTypes(): array {
        return [GroupRecordType::TYPE];
    }
}
