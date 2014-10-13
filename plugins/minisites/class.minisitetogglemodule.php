<?php

class MinisiteToggleModule extends Gdn_Module {
    public $LabelField = 'Name';

    public function ToString() {
        $this->SetData('Sites', MinisiteModel::all());
        $this->SetData('Current', MinisitesPlugin::instance()->getCurrent());

        return parent::ToString();
    }
}
