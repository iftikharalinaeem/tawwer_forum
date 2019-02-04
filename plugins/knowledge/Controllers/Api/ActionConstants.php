<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Knowledge\Controllers\Api;

/**
 * Constants used as redux actions for successful API responses.
 */
abstract class ActionConstants {
    // Knowledge Bases
    const GET_ALL_KBS = "@@knowledgeBases/GET_ALL_DONE";
    const GET_NAVIGATION_FLAT = "@@navigation/GET_NAVIGATION_FLAT_DONE";

    // Article page
    const GET_ARTICLE_RESPONSE = "@@articlePage/GET_ARTICLE_RESPONSE";
    const GET_ARTICLE_ERROR = "@@articlePage/GET_ARTICLE_ERROR";

    // Editor
    const GET_EDITOR_ARTICLE_RESPONSE = "@@articleEditor/GET_ARTICLE_RESPONSE";
    const GET_REVISION_RESPONSE = "@@articleEditor/GET_REVISION_RESPONSE";

    // Category
    const GET_CATEGORY_RESPONSE = "@@kbCategories/GET_RESPONSE";
    const GET_CATEGORY_ERROR = "@@kbCategories/GET_ERROR";
    const GET_ALL_CATEGORIES = "@@kbCategories/GET_ALL_RESPONSE";

    // Category page
    const GET_ARTICLES_RESPONSE = "@@kbCategoriesPage/GET_ARTICLES_RESPONSE";
    const GET_ARTICLES_ERROR = "@@kbCategoriesPage/GET_ARTICLES_ERROR";
}
