<?php
/**
 * @copyright 2008-2016 Vanilla Forums, Inc.
 * @license Proprietary
 */

/**
 * Class NewAnnouncementModule
 */
class NewAnnouncementModule extends Gdn_Module {

    /** @var   */
    public $GroupID;

    /**
     *
     *
     * @return string
     */
    public function toString() {
        if (!$this->GroupID) {
            $GroupID = Gdn::controller()->data('Group.GroupID');
        }

        if (groupPermission('Moderate', $GroupID)) {
            return ' '.anchor(t('New Announcement'), groupUrl(Gdn::controller()->data('Group'), 'announcement'), 'Button').' ';
        }
        return '';
    }
}
