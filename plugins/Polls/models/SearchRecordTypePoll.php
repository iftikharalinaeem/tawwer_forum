<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Polls\Models;

use Vanilla\Contracts\Search\SearchRecordTypeInterface;

class SearchRecordTypePoll implements SearchRecordTypeInterface {
    const PROVIDER_GROUP = 'sphinx';

    const TYPE = 'discussion';

    const CHECKBOX_ID = 'poll';

    const CHECKBOX_LABEL = 'polls';

    /**
     * SearchRecordTypePoll constructor.
     */
    public function __construct() {
        $this->key = self::TYPE;
    }

    /**
     * @inheritdoc
     */
    public function getKey(): string {
        return $this->key;
    }

    /**
     * @inheritdoc
     */
    public function getCheckBoxId(): string {
        return self::TYPE.'_'.self::CHECKBOX_ID;
    }

    /**
     * @inheritdoc
     */
    public function getCheckBoxLabel(): string {
        return self::CHECKBOX_LABEL;
    }

    /**
     * @inheritdoc
     */
    public function getFeatures(): array {
        return $this->structure;
    }

    /**
     * @inheritdoc
     */
    public function getModel() {
        return '';
    }

    /**
     * @inheritdoc
     */
    public function getProviderGroup(): string {
        return self::PROVIDER_GROUP;
    }
}
