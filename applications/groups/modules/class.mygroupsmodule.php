<?php

/**
 * Groups Application - My Groups Module
 *
 * Outputs a list of a user's groups.
 *
 * @author Becky Van Bussel <becky@vanillaforums.com>
 * @copyright 2003 Vanilla Forums, Inc
 * @license Proprietary
 * @package groups
 */

class MyGroupsModule extends Gdn_Module {

    public $Limit = 3;
    public $myGroups = false;
    public $AttachLastDiscussion = true;
    public $Layout;

    private $Groups;
    private $title = 'My Groups';
    private $url = '/groups/browse/mine';
    private $orderBy = 'DateLastComment';

    public function __construct() {
        parent::__construct();
        $this->_ApplicationFolder = 'groups';
    }

    /**
     * Retrieve the groups for this module.
     *
     * @return void
     */
    public function GetData() {
        if (!isset(Gdn::Session()->UserID)) {
            $this->SetData('ErrorMessage', Gdn_Theme::Link('signinout').' '.T('to see your groups.'));
        }
        else {
            //get groups
            $GroupModel = new GroupModel();
            $this->Groups = $GroupModel->GetByUser(Gdn::Session()->UserID, $this->orderBy, false, 'desc', $this->Limit);
            if ($this->AttachLastDiscussion) {
                foreach ($this->Groups as $GroupKey => $GroupValue) {
                  $DiscussionModel = new DiscussionModel();
                    $this->Groups[$GroupKey]['LastDiscussion'] = $DiscussionModel->Get(1, 2, array('GroupID' => $GroupValue['GroupID']))->ResultArray();
                }
            }
        }
    }

    /**
     * Render promoted groups
     *
     * @return type
     */
    public function ToString() {
        $this->GetData();
        $this->SetData('Groups', $this->Groups);
        $this->SetData('Title', $this->title);
        $this->SetData('Url', $this->url);
        $this->SetData('Layout', $this->Layout);

        require_once Gdn::Controller()->FetchViewLocation('group_functions', 'Group', 'groups');

        return $this->FetchView();
    }

}