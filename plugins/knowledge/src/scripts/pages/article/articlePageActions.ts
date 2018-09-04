/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

import { generateApiActionCreators, ActionsUnion, apiThunk, createAction } from "@dashboard/state/utility";
import { IGetArticleRequestBody, IGetArticleResponseBody } from "@knowledge/@types/api";

// Getting an article
export const GET_ARTICLE_REQUEST = "GET_ARTICLE_REQUEST";
export const GET_ARTICLE_SUCCESS = "GET_ARTICLE_SUCCESS";
export const GET_ARTICLE_ERROR = "GET_ARTICLE_ERROR";

export const getArticleActions = generateApiActionCreators(
    GET_ARTICLE_REQUEST,
    GET_ARTICLE_SUCCESS,
    GET_ARTICLE_ERROR,
    // https://github.com/Microsoft/TypeScript/issues/10571#issuecomment-345402872
    {} as IGetArticleResponseBody,
    {} as IGetArticleRequestBody,
);

function getArticle(options: IGetArticleRequestBody) {
    return apiThunk("get", `/articles/${options.id}`, getArticleActions, options);
}

export const thunks = {
    getArticle,
};

export const CLEAR_ARTICLE_PAGE_STATE = "CLEAR_ARTICLE_PAGE_STATE";

export const nonApiActions = {
    clearArticlePageState: () => createAction(CLEAR_ARTICLE_PAGE_STATE),
};

export type ActionTypes = ActionsUnion<typeof getArticleActions & typeof nonApiActions>;
