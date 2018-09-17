/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import { generateApiActionCreators, ActionsUnion, apiThunk, createAction } from "@library/state/utility";

// Action constants
export const GET_ARTICLE_REQUEST = "GET_ARTICLE_REQUEST";
export const GET_ARTICLE_SUCCESS = "GET_ARTICLE_SUCCESS";
export const GET_ARTICLE_ERROR = "GET_ARTICLE_ERROR";
export const RESET_PAGE_STATE = "RESET_ARTICLE_PAGE_STATE";

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

// Non-api related actions for the page.
const nonApiActions = {
    clearArticlePageState: () => createAction(RESET_PAGE_STATE),
};

// Actions made for components to use.
export const componentActions = {
    getArticle,
    ...nonApiActions,
};

// Actions exposed purely for testing purposes.
// You probably should not be using them yourself.
export const _rawApiActions = {
    getArticleActions,
};

export type ActionTypes = ActionsUnion<typeof getArticleActions & typeof nonApiActions>;
