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
            $GroupID = Gdn::controller()->data('Group.GroupID');
        }

        if (groupPermission('Member', $GroupID)) {
            return ' '.anchor(t('New Event'), "/event/add/{$GroupID}", 'Button Primary Group-NewEventButton').' ';
        }
        return '';
    }
}

