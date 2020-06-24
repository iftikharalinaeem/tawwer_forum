<?php
/**
 * @copyright 2008-2016 Vanilla Forums, Inc.
 * @license Proprietary
 */

use Vanilla\Forum\Navigation\GroupRecordType;
use Vanilla\Groups\Models\EventPermissions;

/**
 * Class NewEventModule
 */
class NewEventModule extends Gdn_Module {

    /** @var   */
    public $GroupID;

    /** @var int */
    public $parentRecordID;

    /** @var int */
    public $parentRecordType;

    /**
     *
     *
     * @return string
     */
    public function toString() {
        /** @var EventModel $eventModel */
        $eventModel = \Gdn::getContainer()->get(EventModel::class);

        if (!$this->parentRecordID || !$this->parentRecordType) {
            $this->parentRecordType = GroupRecordType::TYPE;
            $this->parentRecordID = $this->GroupID ?? Gdn::controller()->data('Group.GroupID');
        }

        try {
            $eventModel->checkParentEventPermission(EventPermissions::CREATE, $this->parentRecordType, $this->parentRecordID);
            $newUrl = "/event/add/".$this->parentRecordID."?parentRecordType=".$this->parentRecordType;
            return ' '.anchor(t('New Event'), $newUrl, 'Button Primary Group-NewEventButton').' ';
        } catch (\Garden\Web\Exception\HttpException $e) {
            return '';
        }
    }
}
