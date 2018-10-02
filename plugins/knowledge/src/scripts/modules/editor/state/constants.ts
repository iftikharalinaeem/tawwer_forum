/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

// Route constants
export const EDIT_ROUTE = "/kb/articles/:id/editor";
export const ADD_ROUTE = "/kb/articles/add";
export const ADD_EDIT_ROUTE = "/kb/articles/(\\d+/editor|add)";

// API actions
export const POST_ARTICLE_REQUEST = "@@articleEditor/POST_ARTICLE_REQUEST";
export const POST_ARTICLE_RESPONSE = "@@articleEditor/POST_ARTICLE_RESPONSE";
export const POST_ARTICLE_ERROR = "@@articleEditor/POST_ARTICLE_ERROR";

export const POST_REVISION_REQUEST = "@@articleEditor/POST_REVISION_REQUEST";
export const POST_REVISION_RESPONSE = "@@articleEditor/POST_REVISION_RESPONSE";
export const POST_REVISION_ERROR = "@@articleEditor/POST_REVISION_ERROR";

export const GET_ARTICLE_REQUEST = "@@articleEditor/GET_ARTICLE_REQUEST";
export const GET_ARTICLE_RESPONSE = "@@articleEditor/GET_ARTICLE_RESPONSE";
export const GET_ARTICLE_ERROR = "@@articleEditor/GET_ARTICLE_ERROR";

export const GET_REVISION_REQUEST = "@@articleEditor/GET_REVISION_REQUEST";
export const GET_REVISION_RESPONSE = "@@articleEditor/GET_REVISION_RESPONSE";
export const GET_REVISION_ERROR = "@@articleEditor/GET_REVISION_ERROR";

// Frontend only actions
export const CLEAR_PAGE_STATE = "@@articleEditor/CLEAR_PAGE_STATE";
