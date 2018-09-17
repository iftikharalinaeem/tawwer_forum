/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import { generateApiActionCreators, ActionsUnion, apiThunk, createAction } from "@library/state/utility";
import { IPostArticleResponseBody } from "@knowledge/@types/api";

// Action constants
export const POST_ARTICLE_REQUEST = "POST_ARTICLE_REQUEST";
export const POST_ARTICLE_RESPONSE = "POST_ARTICLE_RESPONSE";
export const POST_ARTICLE_ERROR = "POST_ARTICLE_ERROR";

// Raw actions for getting an article
const postArticleActions = generateApiActionCreators(
    POST_ARTICLE_REQUEST,
    POST_ARTICLE_RESPONSE,
    POST_ARTICLE_ERROR,
    // https://github.com/Microsoft/TypeScript/issues/10571#issuecomment-345402872
    {} as IPostArticleResponseBody,
    {},
);

// Usable action for getting an article
function postArticle(id: number) {
    return apiThunk("post", `/articles/${id}?expand=all`, postArticleActions, {});
}

// Actions made for components to use.
export const componentActions = {
    postArticle,
};

// Actions exposed purely for testing purposes.
// You probably should not be using them yourself.
export const _rawApiActions = {
    postArticleActions,
};

export type ActionTypes = ActionsUnion<typeof postArticleActions>;
