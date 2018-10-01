/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

export const ARTICLE_ROUTE = "/kb/articles/(.*)-:id(\\d+)";

export const GET_ARTICLE_REQUEST = "@@article/GET_ARTICLE_REQUEST";
export const GET_ARTICLE_RESPONSE = "@@article/GET_ARTICLE_RESPONSE";
export const GET_ARTICLE_ERROR = "@@article/GET_ARTICLE_ERROR";
export const RESET_PAGE_STATE = "@@article/RESET_PAGE_STATE";
