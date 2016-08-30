<?php

class NewEventModule extends Gdn_Module {
    /// Properties ///
    public $GroupID;


    /// Methods ///

    public function ToString() {
        if (!$this->GroupID) {
            $GroupID = Gdn::Controller()->Data('Group.GroupID');
        }

        if (GroupPermission('Member', $GroupID)) {
            return ' '.Anchor(T('New Event'), "/event/add/{$GroupID}", 'Button Primary Group-NewEventButton').' ';
        }
        return '';
    }
}

