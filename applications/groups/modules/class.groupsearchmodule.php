<?php

/**
 * Groups Application - Group Module
 *
 * Shows a group box with basic group info.
 *
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2003-2015 Vanilla Forums, Inc
 * @license Proprietary
 */

class GroupSearchModule extends Gdn_Module {

    /**
     * @var Gdn_Form
     */
    public $Form;

    public function __construct() {
        parent::__construct();
        $this->_ApplicationFolder = 'groups';
        $this->Visible = class_exists('Search') && Search::searchGroups();
    }

    public function ToString() {
        if (!$this->Visible) {
            return '';
        }

        $this->Form = new Gdn_Form();
        return $this->FetchView();
    }
}