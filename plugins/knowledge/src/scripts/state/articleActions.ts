/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import { generateApiActionCreators, ActionsUnion, apiThunk, createAction } from "@library/state/utility";
import {
    IPostArticleResponseBody,
    IPostArticleRequestBody,
    IPostArticleRevisionRequestBody,
    IPostArticleRevisionResponseBody,
    IGetArticleResponseBody,
} from "@knowledge/@types/api";

///
/// POST /article
///

export const POST_ARTICLE_REQUEST = "POST_ARTICLE_REQUEST";
export const POST_ARTICLE_RESPONSE = "POST_ARTICLE_RESPONSE";
export const POST_ARTICLE_ERROR = "POST_ARTICLE_ERROR";

const postArticleActions = generateApiActionCreators(
    POST_ARTICLE_REQUEST,
    POST_ARTICLE_RESPONSE,
    POST_ARTICLE_ERROR,
    // https://github.com/Microsoft/TypeScript/issues/10571#issuecomment-345402872
    {} as IPostArticleResponseBody,
    {} as IPostArticleRequestBody,
);

// Usable action for getting an article
function postArticle(requestOptions: IPostArticleRequestBody) {
    return apiThunk("post", `/articles`, postArticleActions, requestOptions);
}

///
/// GET /article
///

// Action constants
export const GET_ARTICLE_REQUEST = "GET_ARTICLE_REQUEST";
export const GET_ARTICLE_SUCCESS = "GET_ARTICLE_SUCCESS";
export const GET_ARTICLE_ERROR = "GET_ARTICLE_ERROR";

// Raw actions for getting an article
const getArticleActions = generateApiActionCreators(
    GET_ARTICLE_REQUEST,
    GET_ARTICLE_SUCCESS,
    GET_ARTICLE_ERROR,
    // https://github.com/Microsoft/TypeScript/issues/10571#issuecomment-345402872
    {} as IGetArticleResponseBody,
    {},
);

// Usable action for getting an article
function getArticle(id: number) {
    return apiThunk("get", `/articles/${id}?expand=all`, getArticleActions, {});
}

// Actions made for components to use.
export const componentActions = {
    getArticle,
    postArticle,
};

// Actions exposed purely for testing purposes.
// You probably should not be using them yourself.
export const _rawApiActions = {
    getArticleActions,
    postArticleActions,
};

export type ActionTypes = ActionsUnion<typeof postArticleActions | typeof getArticleActions>;
