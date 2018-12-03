<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Knowledge\Models;

use Exception;
use Vanilla\Models\Model;

/**
 * Utility class for handling navigation data.
 */
class Navigation {

    // Record type for knowledge categories.
    const RECORD_TYPE_CATEGORY = "knowledgeCategory";

    // Record type for articles.
    const RECORD_TYPE_ARTICLE = "article";

    /**
     * Sync resource rows with a navigation structure.
     *
     * @param array $rows All rows of a particular resource that chould be represented in the tree (i.e. everything in a knowledge base).
     * @param array $navigation Flat list of navigation items.
     * @param string $type Type of resource being synchronized (e.g. article, knowlegeCategory).
     * @param string $idField Unique ID field of the resource being processed (e.g. articleID, knowledgeCategoryID).
     * @param string $parentField Field used to determine organization of the resource (e.g. knowledgeCategoryID, parentID).
     * @param Model $model Database model for performing the resource row updates.
     * @throws Exception If an error is encountered while performing an update query.
     */
    public static function updateAlteredRows(
        array $rows,
        array $navigation,
        string $type,
        string $idField,
        string $parentField,
        Model $model
    ) {
        if ($navigation[0] ?? false) {
            throw new Exception("Navigation array not properly indexed.");
        }

        foreach ($rows as $row) {
            $key = $type."-".$row[$idField];
            $navItem = $navigation[$key] ?? null;
            if ($navItem === null) {
                $model->update(
                    ["sort" => null],
                    [$idField => $row[$idField]]
                );
            } elseif ($navItem["sort"] !== $row["sort"] || $navItem["parentID"] !== $row[$parentField]) {
                $model->update(
                    [
                        "sort" => $navItem["sort"],
                        $parentField => $navItem["parentID"],
                    ],
                    [$idField => $row[$idField]]
                );
            }
        }
    }
}
