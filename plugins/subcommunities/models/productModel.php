<?php

use Vanilla\Database\Operation;

class productModel extends \Vanilla\Models\PipelineModel {

    const FEATURE_FLAG = 'SubcommunityProducts';
    const ENABLED = 'Enabled';
    const DISABLED = 'Disabled';
    
    public function __construct() {
        parent::__construct("product");
        $dateProcessor = new Operation\CurrentDateFieldProcessor();
        $dateProcessor->setInsertFields(["dateInserted", "dateUpdated"])
            ->setUpdateFields(["dateUpdated"]);
        $this->addPipelineProcessor($dateProcessor);
    }

}