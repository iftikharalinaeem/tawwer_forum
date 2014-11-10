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
        if (!Gdn::Session()->UserID > 0) {
            $this->SetData('ErrorMessage', Gdn_Theme::Link('signinout').' '.T('to see your groups.'));
        }
        else {
            //get groups
            $GroupModel = new GroupModel();
            $this->Groups = $GroupModel->GetByUser(Gdn::Session()->UserID, $this->orderBy, 'desc', $this->Limit);
            if (!is_array($this->Groups) || !count($this->Groups) > 0) {
               $this->SetData('ErrorMessage', T("You haven't joined any groups yet.").' '.Anchor(T('Browse popular groups.'), '/groups/browse/popular'));
            }
            else if ($this->AttachLastDiscussion) {
               $GroupModel->JoinRecentPosts($this->Groups);
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