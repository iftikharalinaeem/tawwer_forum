<?php

class SubcommunityToggleModule extends Gdn_Module {
    public $LabelField = 'Name';
    public $Style = '';

    public function __construct($Sender = '', $ApplicationFolder = false) {
        $this->_Sender = $Sender;
        $this->_ApplicationFolder = 'plugins/subcommunities';
    }

    public function toString() {
        if (!$this->Visible) {
            return '';
        }
        $style = strtolower($this->Style);
        if (in_array($style, array('select'))) {
            $this->setView("subcommunitytoggle_$style");
        }
        $this->setData('Subcommunities', SubcommunityModel::getAvailable());
        $this->setData('Current', SubcommunityModel::getCurrent());

        return parent::fetchView($this->getView());
    }
}
