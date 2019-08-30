<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */
namespace Vanilla\Subcommunities\Models;

use Garden\Schema\ValidationException;
use Vanilla\Database\Operation;
use Vanilla\Exception\Database\NoResultsException;
use Garden\Schema\Schema;

/**
 * A model for managing products.
 */
class ProductModel extends \Vanilla\Models\PipelineModel {

    const FEATURE_FLAG = 'SubcommunityProducts';
    const ENABLED = 'Enabled';
    const DISABLED = 'Disabled';

    /**
     * @var array
     */
    public $productFragmentSchema;

    /**
     * ProductModel constructor.
     */
    public function __construct() {
        parent::__construct("product");
        $dateProcessor = new Operation\CurrentDateFieldProcessor();
        $dateProcessor->setInsertFields(["dateInserted", "dateUpdated"])
            ->setUpdateFields(["dateUpdated"]);
        $this->addPipelineProcessor($dateProcessor);
        $this->productFragmentSchema = $this->setProductFragmentSchema();
    }

    /**
     * Add multi-dimensional category data to an array.
     *
     * @param array $rows Results we need to associate category data with.
     */
    public function expandProduct(array &$rows) {
        if (count($rows) === 0) {
            // Nothing to do here.
            return;
        }

        reset($rows);
        $single = is_string(key($rows));

        $populate = function(array &$row) {
            if (array_key_exists('ProductID', $row) && !is_null($row['ProductID'])) {
                try{
                    $product = $this->selectSingle(["productID" => $row['ProductID']]);
                    if ($product) {
                        setValue('product', $row, $product);
                    }
                } catch (NoResultsException $e) {
                    logException($e);
                }
            }
        };

        if ($single) {
            $populate($rows);
        } else {
            foreach ($rows as &$row) {
                $populate($row);
            }
        }
    }

    private function setProductFragmentSchema() {
        $this->productFragmentSchema = [
            "productID",
            "name",
            "body",
        ];
    }
}
