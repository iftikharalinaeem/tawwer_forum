<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Knowledge\Controllers\Api;

/**
 * Constants used as redux actions for successful API responses.
 */
abstract class ArticlesApiActions {
    const GET_ARTICLE_RESPONSE = "@@article/GET_ARTICLE_RESPONSE";
    const GET_CATEGORY_RESPONSE = "@@kbCategories/GET_RESPONSE";
    const GET_ARTICLES_RESPONSE = "@@article/GET_ARTICLES_RESPONSE";
}
