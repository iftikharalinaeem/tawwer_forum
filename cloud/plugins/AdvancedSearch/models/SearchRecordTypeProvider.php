<?php
/**
 * @author Alexander Kim <alexander.k@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\AdvancedSearch\Models;

use Vanilla\Contracts\Search\SearchRecordTypeInterface;
use Vanilla\Contracts\Search\SearchRecordTypeProviderInterface;

/**
 * Class SearchRecordTypeProvider
 * @package Vanilla\AdvancedSearch\Models
 */
class SearchRecordTypeProvider implements SearchRecordTypeProviderInterface {
    /** @var $searchRecordTypes SearchRecordTypeInterface[] */
    private $types = [];

    /** @var $providerGroups string[] */
    private $providerGroups = [];

    /** @var \Gdn_Session $session */
    private $session;

    /**
     * SearchRecordTypeProvider constructor.
     * @param \Gdn_Session $session
     */
    public function __construct(\Gdn_Session $session) {
        $this->session = $session;
    }

    /**
     * @inheritdoc
     */
    public function getAll(): array {
        $res = [];
        foreach ($this->types as $recordType) {
            if (in_array($recordType->getProviderGroup(), $this->providerGroups)
                && $recordType->isEnabled($this->session)) {
                $res[] = $recordType;
            }
        }
        return $res;
    }

    /**
     * @inheritdoc
     */
    public function setType(SearchRecordTypeInterface $recordType) {
        $this->types[] = $recordType;
    }

    /**
     * @inheritdoc
     */
    public function getType(string $typeKey): ?SearchRecordTypeInterface {
        $result = null;
        foreach ($this->types as $type) {
            if ($type->getKey() === $typeKey) {
                $result = $type;
                break;
            }
        }
        return $result;
    }

    /**
     * @inheritdoc
     */
    public function getByDType(int $dtype): ?SearchRecordTypeInterface {
        $result = null;
        foreach ($this->types as $recordType) {
            if (in_array($recordType->getProviderGroup(), $this->providerGroups)) {
                if ($dtype === $recordType->getDType()) {
                    $result = $recordType;
                    break;
                }
            }
        }
        return $result;
    }

    /**
     * @inheritdoc
     */
    public function addProviderGroup(string $providerGroup) {
        $this->providerGroups[] = $providerGroup;
    }
}