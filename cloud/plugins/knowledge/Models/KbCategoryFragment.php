<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Knowledge\Models;

use Garden\Schema\Schema;
use Garden\Schema\ValidationException;
use Vanilla\Navigation\Breadcrumb;

/**
 * Record for a knowledge category fragment.
 */
final class KbCategoryFragment {

    /** @var int */
    private $knowledgeBaseID;

    /** @var $knowledgeCategoryID */
    public $knowledgeCategoryID;

    /** @var int */
    private $parentID;

    /** @var string */
    private $url;

    /** @var int|null */
    private $sort;

    /** @var string */
    private $name;

    /**
     * KbCategoryFragment constructor.
     *
     * @param array $arr The data to create the instance from.
     *
     * @throws ValidationException If invalid data was provided.
     */
    public function __construct(array $arr) {
        $parsed = self::schema()->validate($arr);
        foreach ($parsed as $prop => $val) {
            $this->{$prop} = $val;
        }
    }

    /**
     * Get the validation schema for this fragment.
     *
     * @return Schema
     */
    private function schema(): Schema {
        static $schema;
        if (!$schema) {
            $schema = Schema::parse([
                "knowledgeCategoryID:i",
                "name" => [
                    "length" => 255,
                    "type" => "string",
                ],
                "parentID:i",
                "url:s",
                "knowledgeBaseID:i?",
                "sort:i?",
            ]);
        }
        return $schema;
    }

    /**
     * Convert the fragment into a breadcrumb.
     *
     * @return Breadcrumb
     */
    public function asBreadcrumb(): Breadcrumb {
        return new Breadcrumb($this->getName(), $this->getUrl());
    }

    /**
     * @return int
     */
    public function getKnowledgeBaseID(): int {
        return $this->knowledgeBaseID;
    }

    /**
     * @return mixed
     */
    public function getKnowledgeCategoryID() {
        return $this->knowledgeCategoryID;
    }

    /**
     * @return int
     */
    public function getParentID(): int {
        return $this->parentID;
    }

    /**
     * @return string
     */
    public function getUrl(): string {
        return $this->url;
    }

    /**
     * @return int|null
     */
    public function getSort(): int {
        return $this->sort;
    }

    /**
     * @return string
     */
    public function getName(): string {
        return $this->name;
    }
}
