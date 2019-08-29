<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

use Vanilla\Database\Operation;

/**
 * A model for managing products.
 */
class ProductModel extends \Vanilla\Models\PipelineModel {

    const FEATURE_FLAG = 'SubcommunityProducts';
    const ENABLED = 'Enabled';
    const DISABLED = 'Disabled';

    /**
     * ProductModel constructor.
     */
    public function __construct() {
        parent::__construct("product");
        $dateProcessor = new Operation\CurrentDateFieldProcessor();
        $dateProcessor->setInsertFields(["dateInserted", "dateUpdated"])
            ->setUpdateFields(["dateUpdated"]);
        $this->addPipelineProcessor($dateProcessor);
    }
}
