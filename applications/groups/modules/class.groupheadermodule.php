<?php
/**
 * Groups Application - Group Header Module
 */

/**
 * Class GroupHeaderModule
 *
 *
 */
class GroupHeaderModule extends Gdn_Module {

    /**
     * @var array The group the header is associated with.
     */
    public $group;
    /**
     * @var bool Whether to include the group edit options.
     */
    public $showOptions;
    /**
     * @var bool Whether to include the group buttons (i.e., 'Join').
     */
    public $showButtons;
    /**
     * @var bool Whether to include the group meta.
     */
    public $showMeta;
    /**
     * @var bool Whether to include the group description.
     */
    public $showDescription;

    /**
     * Construct the GroupHeaderModule object.
     *
     * @param array $group The group the header is associated with.
     * @param bool $showOptions Whether to include the group edit options.
     * @param bool $showButtons Whether to include the group buttons (i.e., 'Join').
     * @param bool $showMeta Whether to include the group meta.
     * @param bool $showDescription Whether to include the group description.
     */
    function __construct($group = array(), $showOptions = true, $showButtons = true, $showMeta = false, $showDescription = false) {
        $this->group = $group;
        $this->showOptions = $showOptions;
        $this->showButtons = $showButtons;
        $this->showMeta = $showMeta;
        $this->showDescription = $showDescription;
    }

    /**
     * Defines 'Content' as the target for the module.
     *
     * @return string The module's target.
     */
    public function assetTarget() {
        return 'Content';
    }

    /**
     * Renders the group header.
     *
     * @return string HTML view
     */
    public function toString() {
        include_once(PATH_APPLICATIONS.'/groups/views/group/group_functions.php');
        if (!$this->group) {
            $controller = Gdn::controller();
            $this->group = val('Group', $controller->Data);
        }
        if (!$this->group) {
            return '';
        }
        return $this->fetchView();
    }
}
