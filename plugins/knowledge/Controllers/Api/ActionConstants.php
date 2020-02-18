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

    const ARTICLE_TRANSLATION_FALLBACK = "@@article/ARTICLE_USES_TRANSLATION_FALLBACK";

    // Article page
    const GET_ARTICLE_LOCALES = "@@article/GET_LOCALES_DONE";
    const GET_ARTICLE_RESPONSE = "@@article/GET_ARTICLE_DONE";
    const GET_ARTICLE_ERROR = "@@article/GET_ARTICLE_FAILED";
    const GET_RELATED_ARTICLES = "@@article/GET_RELATED_ARTICLES_DONE";
    const GET_ARTICLE_LIST = "@@article/GET_ARTICLE_LIST";

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

    // Server data
    const SET_LOCAL_DEPLOYMENT_KEY = "@@server/SET_LOCAL_DEPLOYMENT_KEY";
}
