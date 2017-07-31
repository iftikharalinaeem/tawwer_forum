<?php

class SubcommunityToggleModule extends Gdn_Module {
    public $LabelField = 'Name';
    public $Style = '';

    public function __construct($sender = '', $applicationFolder = false) {
        $this->_Sender = $sender;
        $this->_ApplicationFolder = 'plugins/subcommunities';
    }

    public function toString() {
        if (!$this->Visible) {
            return '';
        }
        $style = strtolower($this->Style);
        if (in_array($style, ['select'])) {
            $this->setView("subcommunitytoggle_$style");
        }
        $this->setData('Subcommunities', SubcommunityModel::getAvailable());
        $this->setData('Current', SubcommunityModel::getCurrent());

        return parent::fetchView($this->getView());
    }
}
