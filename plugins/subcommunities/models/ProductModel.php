<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */
namespace Vanilla\Subcommunities\Models;


use Vanilla\Database\Operation;
use Vanilla\Exception\Database\NoResultsException;
use Garden\Schema\Schema;
use Gdn_Session;

/**
 * A model for managing products.
 */
class ProductModel extends \Vanilla\Models\PipelineModel {

    const FEATURE_FLAG = 'SubcommunityProducts';

    /** @var Gdn_Session */
    private $session;

    /** @var Schema */
    public $productSchema;

    /**
     * ProductModel constructor.
     *
     * @param Gdn_Session $session
     */
    public function __construct(Gdn_Session $session) {
        parent::__construct("product");
        $this->session = $session;
        $dateProcessor = new Operation\CurrentDateFieldProcessor();
        $dateProcessor->setInsertFields(["dateInserted", "dateUpdated"])
            ->setUpdateFields(["dateUpdated"]);
        $this->addPipelineProcessor($dateProcessor);

        $userProcessor = new Operation\CurrentUserFieldProcessor($this->session);
        $userProcessor->setInsertFields(["insertUserID", "updateUserID"])
            ->setUpdateFields(["updateUserID"]);
        $this->addPipelineProcessor($userProcessor);
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

        $populate = function (array &$row) {
            if (array_key_exists('ProductID', $row) && !is_null($row['ProductID'])) {
                try {
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

    /**
     * Simplified product schema.
     *
     * @param string $type
     * @return Schema
     */
    public function productFragmentSchema(string $type = ""): Schema {
        if ($this->productSchema === null) {
            $this->productSchema = Schema::parse([
                "productID",
                "name",
                "body?",
            ], $type);
        }
        return $this->productSchema;
    }
}
