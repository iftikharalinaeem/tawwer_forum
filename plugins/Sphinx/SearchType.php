<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Sphinx;


/**
 * This class defines the fields that a type needs to add in order to be searched.
 *
 * Instances of this class are added using`SphinxSearchModel::addSearchType()`.
 *
 * This class is just a structured data container and should not be given any dependencies.
 */
class SearchType {
    /**
     * @var string
     */
    private $apiValue = '';

    /**
     * @var string
     */
    private $oldAPIField = '';

    /**
     * @var string[]
     */
    private $indexes = [];

    /**
     * @var int
     */
    private $attributeValue = 0;

    /**
     * @var string
     */
    private $labelCode = '';

    /**
     * This is the value that the API expects in order to search this type.
     *
     * So for example I want to have "articles" be submitted to the endpoint to search articles.
     *
     * @return string
     */
    public function getApiValue(): string {
        return $this->apiValue;
    }

    /**
     * Set the API value.
     *
     * @param string $apiVaue The API value.
     * @return $this
     */
    public function setApiValue(string $apiValue) {
        $this->apiValue = $apiValue;
        return $this;
    }

    /**
     * The names of the Sphinx indexes that this type resides in.
     *
     * Using this field helps the search know
     *
     * @return string[] Returns an array of index names.
     */
    public function getIndexes(): array {
        return $this->indexes;
    }

    /**
     * Set the Sphinx indexes that contain this type.
     *
     * @param string ...$indexes The names of the indexes.
     * @return $this
     */
    public function setIndexes(string ...$indexes) {
        $this->indexes = $indexes;
        return $this;
    }

    /**
     * The value within the sphinx index in order to filter to this type.
     *
     * This value maps to the `dtype` attribute in the sphinx template.
     *
     * @return int
     */
    public function getAttributeValue(): int {
        return $this->attributeValue;
    }

    /**
     * @param int $attributeValue
     * @return $this
     */
    public function setAttributeValue(int $attributeValue) {
        $this->attributeValue = $attributeValue;
        return $this;
    }

    /**
     * The translation code that a user interface can use when displaying a filter for this type.
     *
     * @return string
     */
    public function getLabelCode(): string {
        return $this->labelCode;
    }

    /**
     * @param string $labelCode
     * @return $this
     */
    public function setLabelCode(string $labelCode) {
        $this->labelCode = $labelCode;
        return $this;
    }

    /**
     * The old search module used an awkward field name for type checkboxes.
     *
     * This field is used for backwards comparability.
     *
     * @return string
     */
    public function getOldAPIField(): string {
        return $this->oldAPIField;
    }

    /**
     * @param string $oldAPIField The name of the old field.
     * @return $this
     */
    public function setOldAPIField(string $oldAPIField) {
        $this->oldAPIField = $oldAPIField;
        return $this;
    }
}
