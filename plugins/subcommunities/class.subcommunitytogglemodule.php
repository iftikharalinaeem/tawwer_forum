<?php

class SubcommunityToggleModule extends Gdn_Module {
    public $LabelField = 'Name';
    public $Style = '';

    public function __construct($Sender = '', $ApplicationFolder = FALSE) {
        $this->_Sender = $Sender;
        $this->_ApplicationFolder = 'plugins/subcommunities';
    }

    public function ToString() {
        if (!$this->Visible) {
            return '';
        }
        $style = strtolower($this->Style);
        if (in_array($style, array('select'))) {
            $this->setView("subcommunitytoggle_$style");
        }
        $this->SetData('Subcommunities', SubcommunityModel::getAvailable());
        $this->SetData('Current', SubcommunityModel::getCurrent());

        return parent::FetchView($this->getView());
    }
}
