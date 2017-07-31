<?php
/**
 * @copyright 2008-2016 Vanilla Forums, Inc.
 * @license Proprietary
 */

/**
 * Groups Application - Event Module
 *
 * Shows a small events list based on the provided Group or User context.
 *
 * @author Tim Gunter <tim@vanillaforums.com>
 * @package groups
 * @since 1.0
 */
class EventModule extends Gdn_Module {

    /** @var null  */
    protected $Filter = null;

    /** @var null  */
    protected $FilterBy = null;

    /** @var null  */
    protected $Type = null;

    /** @var null  */
    protected $Button = null;

    /**
     * EventModule constructor.
     *
     * @param null $type
     * @param null $filterBy
     * @param null $filter
     * @param null $button
     */
    public function __construct($type = null, $filterBy = null, $filter = null, $button = null) {
        parent::__construct();
        $this->_ApplicationFolder = 'groups';

        if (!is_null($type))
            $this->Type = $type;

        if (!is_null($filterBy))
            $this->FilterBy = $filterBy;

        if (!is_null($filter))
            $this->Filter = $filter;

        if (!is_null($button))
            $this->Button = $button;
    }

    public function __set($name, $value) {
        $name = strtolower($name);
        switch ($name) {
            case 'groupid':
                $this->Filter = $value;
                $this->FilterBy = 'group';
                break;

            case 'userid':
                $this->Filter = $value;
                $this->FilterBy = 'user';
                break;

            case 'type':
                $this->Type = $value;
                break;

            case 'button':
                $this->Button = $value;
                break;
        }

        return $this;
    }

    public function GetData() {

        // Only callable if configured
        if (!$this->Type) return;

        // Callable multiple times
        if (!is_null($this->Data('Events', null))) return;

        $eventCriteria = [];
        switch ($this->FilterBy) {
            case 'group':
                $groupModel = new GroupModel();
                $group = $groupModel->GetID($this->Filter, DATASET_TYPE_ARRAY);
                $this->SetData('Group', $group);
                $eventCriteria['GroupID'] = $group['GroupID'];
                break;

            case 'user':
                $user = Gdn::UserModel()->GetID($this->Filter, DATASET_TYPE_ARRAY);
                $this->SetData('User', $user);
                $eventCriteria['Invited'] = $user['UserID'];
                break;
        }

        switch ($this->Type) {
            case 'upcoming':
                $filterDate = C('Groups.Events.UpcomingRange', '+365 days');
                $ended = false;
                $this->SetData('Title', T('Upcoming Events'));
                break;

            case 'recent':
                $filterDate = C('Groups.Events.RecentRange', '-365 days');
                $ended = true;
                $this->SetData('Title', T('Recent Events'));
                break;
        }

        $eventModel = new EventModel();
        $this->SetData('Events', $eventModel->GetUpcoming($filterDate, $eventCriteria, $ended));

    }

    public function ToString() {
        $this->GetData();
        if (!is_null($this->Button))
            $this->SetData('Button', $this->Button);
        return $this->FetchView();
    }

}
