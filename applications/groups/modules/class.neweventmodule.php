<?php
/**
 * @copyright 2008-2016 Vanilla Forums, Inc.
 * @license Proprietary
 */

/**
 * Class NewEventModule
 */
class NewEventModule extends Gdn_Module {

    /** @var   */
    public $GroupID;

    /**
     *
     *
     * @return string
     */
    public function ToString() {
        if (!$this->GroupID) {
            $groupID = Gdn::controller()->data('Group.GroupID');
        }

        if (groupPermission('Member', $groupID)) {
            return ' '.anchor(t('New Event'), "/event/add/{$groupID}", 'Button Primary Group-NewEventButton').' ';
        }
        return '';
    }
}

