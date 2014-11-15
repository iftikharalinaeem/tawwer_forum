<?php

class SubcommunityToggleModule extends Gdn_Module {
    public $LabelField = 'Name';

    public function __construct($Sender = '', $ApplicationFolder = FALSE) {
        $this->_Sender = $Sender;
        $this->_ApplicationFolder = 'plugins/subcommunities';
    }

    public function ToString() {
        $this->SetData('Sites', SubcommunityModel::all());
        $this->SetData('Current', SubcommunityModel::getCurrent());

        return parent::ToString();
    }
}
