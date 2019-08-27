<?php

class productModel extends \Vanilla\Models\PipelineModel {

    const FEATURE_FLAG = 'SubcommunityProducts';
    
    public function __construct() {
        parent::__construct("product");
    }

}