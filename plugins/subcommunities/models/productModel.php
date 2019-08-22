<?php

class productModel extends \Vanilla\Models\PipelineModel {

    /** Default limit on the number of results returned. */
    const LIMIT_DEFAULT = 1000;

    public function __construct() {
        parent::__construct("product");
    }

    /**
     * Get resource rows from a database table.
     *
     * @param array $where Conditions for the select query.
     * @param array $options Options for the select query.
     *    - orderFields (string, array): Fields to sort the result by.
     *    - orderDirection (string): Sort direction for the order fields.
     *    - limit (int): Limit on the total results returned.
     *    - offset (int): Row offset before capturing the result.
     * @return array Rows matching the conditions and within the parameters specified in the options.
     * @throws ValidationException If a row fails to validate against the schema.
     */
    public function get(array $where = [], array $options = []): array {
        $options["limit"] = $options["limit"] ?? self::LIMIT_DEFAULT;
        return parent::get($where, $options);
    }

    /**
     * Get a single article row by its ID.
     *
     * @param int $productID
     * @return array
     * @throws ValidationException If the result fails schema validation.
     * @throws NoResultsException If the article could not be found.
     */
    public function getID(int $productID): array {
        return $this->selectSingle(["$productID" => $productID]);
    }


}