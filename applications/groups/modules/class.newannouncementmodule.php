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
    public function ToString() {
        if (!$this->GroupID) {
            $GroupID = Gdn::Controller()->Data('Group.GroupID');
        }

        if (GroupPermission('Moderate', $GroupID)) {
            return ' '.Anchor(T('New Announcement'), GroupUrl(Gdn::Controller()->Data('Group'), 'announcement'), 'Button').' ';
        }
        return '';
    }
}
