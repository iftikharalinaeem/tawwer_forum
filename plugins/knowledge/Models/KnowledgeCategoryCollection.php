<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Knowledge\Models;

use Garden\Web\Exception\ServerException;

/**
 * Simple class for grouping/sorting a set of knowledge categories.
 */
final class KnowledgeCategoryCollection {

    /** @var array */
    private $categories;

    /** @var array */
    private $rootCategory;

    /** @var array */
    private $categoriesByID;

    /**
     * An array of IDs that are currently being walked.
     *
     * If the one of these is reached again, a warning will be logged and recursion will be stopped.
     *
     * @var int[]
     */
    private $walkingRecursionGaurdIDs = [];

    /**
     * Constructor.
     *
     * @param array $categories The categories to use in the collection. All should be from the same knowledge base.
     */
    public function __construct(array $categories) {
        $this->categories = $categories;

        $rootCategories = array_filter($this->categories, function (array $category) {
            return $category['parentID'] === KnowledgeCategoryModel::ROOT_ID;
        });
        if (count($rootCategories) !== 1) {
            trigger_error('There must be exactly 1 root category in a category collection', E_USER_WARNING);
        }
        $this->rootCategory = $rootCategories[0];
        if (!$this->rootCategory) {
            throw new \Exception('A category collection requires a root category');
        }

        $this->categoriesByID = array_column($this->categories, null, 'knowledgeCategoryID');
    }

    /**
     * Get a category by it's ID.
     *
     * @param int $categoryID
     *
     * @return array|null
     */
    public function getByID(int $categoryID): ?array {
        return $this->categoriesByID[$categoryID] ?? null;
    }

    /**
     * Group categories by their top level parents.
     *
     * @example The following Structure will be grouped as follows
     * INPUT
     * - Cat 1
     *   - Cat 1.1
     *     - Cat 1.1.1
     *   - Cat 1.2
     * - Cat 2
     * - Cat 3
     *   - Cat 3.1
     *
     * OUTPUT
     * - [ Cat 1, Cat 1.1, Cat 1.1.1, Cat 1.2 ]
     * - [ Cat 2 ]
     * - [ Cat 3, Cat 3.2]
     *
     * @return array
     */
    public function groupCategoriesByTopLevel(): array {
        $resultByParentID = [];

        foreach ($this->categories as $category) {
            $parent = $this->walkParentToRootCategory($category['knowledgeCategoryID']);
            $parentID = $parent['knowledgeCategoryID'];
            if (isset($resultByParentID[$parentID])) {
                $resultByParentID[$parentID][] = $category;
            } else {
                $resultByParentID[$parentID] = [$category];
            }
        }

        return $resultByParentID;
    }

    /**
     * Walk a set of category parents until a root is reached.
     *
     * @example Given the following structure
     *
     * - Synthetic Root (Not part of the collection)
     *   - Root
     *     - Cat 1
     *       - Cat 1.1
     *         - Cat 1.1.1
     *
     * INPUT: Cat 1.1.1
     * OUTPUT: Cat 1
     *
     * @param int $categoryID The categoryID to start from.
     *
     * @return array|null The root category.
     */
    private function walkParentToRootCategory(int $categoryID): ?array {
        $category = $this->getByID($categoryID);
        if (!$category) {
            $this->walkingRecursionGaurdIDs = [];
            return null;
        }

        $parentID = $category['parentID'];
        if ($parentID === $this->rootCategory['knowledgeCategoryID'] || $parentID === KnowledgeCategoryModel::ROOT_ID) {
            $this->walkingRecursionGaurdIDs = [];
            return $category;
        }

        if (in_array($parentID, $this->walkingRecursionGaurdIDs)) {
            // We've been here before.
            trigger_error(
                "Infinite recursion prevented while walking categories. Category $parentID is both a parent and child to Category $categoryID",
                E_USER_WARNING
            );
            return null;
        }
        return $this->walkParentToRootCategory($parentID);
    }
}

