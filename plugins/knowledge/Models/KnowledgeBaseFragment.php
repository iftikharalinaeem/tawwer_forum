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
 * Record for a knowledge base fragment.
 */
final class KnowledgeBaseFragment {

    /** @var int */
    private $knowledgeBaseID;

    /** @var string */
    private $rootCategoryID;

    /** @var string */
    private $viewType;

    /** @var string */
    private $status;

    /** @var string */
    private $url;

    /** @var string */
    private $name;

    /**
     * KnowledgeBaseFragment constructor.
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
                "knowledgeBaseID:i",
                "rootCategoryID:i",
                "name" => [
                    "length" => 255,
                    "type" => "string",
                ],
                "url:s",
                "viewType:s",
                "status:s",
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
     * @return string
     */
    public function getRootCategoryID(): string {
        return $this->rootCategoryID;
    }

    /**
     * @return string
     */
    public function getViewType(): string {
        return $this->viewType;
    }

    /**
     * @return string
     */
    public function getStatus(): string {
        return $this->status;
    }

    /**
     * @return string
     */
    public function getUrl(): string {
        return $this->url;
    }

    /**
     * @return string
     */
    public function getName(): string {
        return $this->name;
    }
}
