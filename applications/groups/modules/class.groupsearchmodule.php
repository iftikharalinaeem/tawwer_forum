<?php
/**
 * @copyright 2008-2016 Vanilla Forums, Inc.
 * @license Proprietary
 */

/**
 * Groups Application - Group Module
 *
 * Shows a group box with basic group info.
 *
 * @author Todd Burry <todd@vanillaforums.com>
 */
class GroupSearchModule extends Gdn_Module {

    /** @var Gdn_Form */
    public $Form;

    /**
     * GroupSearchModule constructor.
     */
    public function __construct() {
        parent::__construct();
        $this->_ApplicationFolder = 'groups';
        $this->Visible = class_exists('Search') && Search::searchGroups();
    }

    /**
     * @return string
     */
    public function toString() {
        if (!$this->Visible) {
            return '';
        }

        $this->Form = new Gdn_Form();
        return $this->fetchView();
    }
}