<?php

class SubcommunityToggleModule extends Gdn_Module {
    public $LabelField = 'Name';

    public function ToString() {
        $this->SetData('Sites', SubcommunityModel::all());
        $this->SetData('Current', SubcommunityModel::getCurrent());

        return parent::ToString();
    }
}
