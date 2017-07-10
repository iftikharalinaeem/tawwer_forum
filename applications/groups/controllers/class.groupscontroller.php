<?php
/**
 * @copyright 2008-2016 Vanilla Forums, Inc.
 * @license Proprietary
 */

/**
 * Class GroupsController
 *
 * @package groups
 */
class GroupsController extends Gdn_Controller {

    /** @var array  */
    public $Uses = ['GroupModel'];

    /** @var GroupModel */
    public $GroupModel;

    /** @var int The page size of groups when browsing. */
    public $PageSize = 24;

    /**
     * Include JS, CSS, and modules used by all methods.
     *
     * Always called by dispatcher before controller's requested method.
     *
     * @access public
     */
    public function initialize() {
        // Set up head
        $this->Head = new HeadModule($this);
        $this->addJsFile('jquery.js');
        $this->addJsFile('jquery.livequery.js');
        $this->addJsFile('jquery-ui.js');
        $this->addJsFile('jquery.tokeninput.js');
        $this->addJsFile('jquery.form.js');
        $this->addJsFile('jquery.popup.js');
        $this->addJsFile('jquery.gardenhandleajaxform.js');
        $this->addJsFile('global.js');
        $this->addJsFile('group.js');
        $this->addCssFile('vanillicon.css', 'static');
        $this->addCssFile('style.css');

        $this->addBreadcrumb(t('Groups'), '/groups');
        parent::initialize();
    }

    /**
     *
     *
     * @param int $Limit
     * @throws Exception
     */
    public function index($Limit = 9) {
        Gdn_Theme::section('GroupList');

        if (!is_numeric($Limit)) {
             $Limit = 9;
        } elseif ($Limit > 30) {
             $Limit = 30;
        } elseif ($Limit < 0) {
             $Limit = 9;
        }

        // Get popular groups.
        $Groups = $this->GroupModel->get('CountMembers', 'desc', $Limit)->resultArray();
        $this->setData('Groups', $Groups);

        // Get new groups.
        $NewGroups = $this->GroupModel->get('DateInserted', 'desc', $Limit)->resultArray();
        $this->setData('NewGroups', $NewGroups);

        // Get my groups.
        if (Gdn::session()->isValid()) {
            $MyGroups = $this->GroupModel->getByUser(Gdn::session()->UserID, $Limit);
            $this->setData('MyGroups', $MyGroups);
        }

        if ($this->deliveryType() !== DELIVERY_TYPE_DATA) {
            $this->title(t('Groups'));
            require_once $this->fetchViewLocation('group_functions', 'Group');
            $this->CssClass .= ' NoPanel';
        }
        $this->render('Groups');
    }

    /**
     *
     *
     * @param string $Sort
     * @param string $Page
     * @throws Exception
     */
    public function browse($Sort = 'newest', $Page = '') {
        Gdn_Theme::section('GroupList');
        $Sort = strtolower($Sort);

        $Sorts = [
            'new' => ['Title' => t('New Groups'), 'OrderBy' => 'DateInserted'],
            'popular' => ['Title' => t('Popular Groups'), 'OrderBy' => 'CountMembers'],
            'updated' => ['Title' => t('Recently Updated Groups'), 'OrderBy' => 'DateLastComment'],
            'mine' => ['Title' => t('My Groups'), 'OrderBy' => 'DateInserted']
        ];

        if (!array_key_exists($Sort, $Sorts)) {
            $Sort = array_pop(array_keys($Sorts));
        }

        $SortRow = $Sorts[$Sort];
        $PageSize = $this->PageSize; // good size for 4, 3, 2 columns.
        list($Offset, $Limit) = offsetLimit($Page, $PageSize);
        $PageNumber = pageNumber($Offset, $Limit);

        if (Gdn::session()->UserID && $Sort == 'mine') {
             $Groups = $this->GroupModel->getByUser(Gdn::session()->UserID, '', 'desc', $Limit, $Offset);
        } else {
             $Groups = $this->GroupModel->get($SortRow['OrderBy'], 'desc', $Limit, $PageNumber)->resultArray();
             $TotalRecords = $this->GroupModel->getCount();
        }
        $this->setData('Groups', $Groups);

        // Set the pager data.
        $this->setData('_Limit', $Limit);
        $this->setData('_CurrentRecords', count($Groups));

        $Pager = PagerModule::current();
        // Use simple pager for 'mine'
        if (Gdn::session()->UserID && $Sort != 'mine') {
            $Pager->configure($Offset, $Limit, $TotalRecords, "groups/browse/$Sort/{Page}");
        }

        $this->title($SortRow['Title']);
        $this->addBreadcrumb($this->title(), "/groups/browse/$Sort");

        require_once $this->fetchViewLocation('group_functions', 'Group');
        $this->render();
    }
}
