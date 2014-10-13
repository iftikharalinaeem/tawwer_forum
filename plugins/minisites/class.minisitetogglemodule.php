<?php

class MinisiteToggleModule extends Gdn_Module {
    public $LabelField = 'Name';

    public function ToString() {
        $this->SetData('Sites', MinisiteModel::all());
        $this->SetData('Current', MinisiteModel::getCurrent());

        return parent::ToString();
    }
}
