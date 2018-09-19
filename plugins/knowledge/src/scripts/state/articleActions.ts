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
} from "@knowledge/@types/api";
import { History } from "history";

// Action constants
export const POST_ARTICLE_REQUEST = "POST_ARTICLE_REQUEST";
export const POST_ARTICLE_RESPONSE = "POST_ARTICLE_RESPONSE";
export const POST_ARTICLE_ERROR = "POST_ARTICLE_ERROR";
export const POST_REVISION_REQUEST = "POST_REVISION_REQUEST";
export const POST_REVISION_RESPONSE = "POST_REVISION_RESPONSE";
export const POST_REVISION_ERROR = "POST_REVISION_ERROR";

// Usable action for getting an article
interface IPostArticleMeta {
    requestOptions: IPostArticleRequestBody;
    history: History;
}

// Raw actions for getting an article
const postArticleActions = generateApiActionCreators(
    POST_ARTICLE_REQUEST,
    POST_ARTICLE_RESPONSE,
    POST_ARTICLE_ERROR,
    // https://github.com/Microsoft/TypeScript/issues/10571#issuecomment-345402872
    {} as IPostArticleResponseBody,
    {} as IPostArticleMeta,
);

// Raw actions for getting an article
const postRevisionActions = generateApiActionCreators(
    POST_REVISION_REQUEST,
    POST_REVISION_RESPONSE,
    POST_REVISION_ERROR,
    // https://github.com/Microsoft/TypeScript/issues/10571#issuecomment-345402872
    {} as IPostArticleRevisionResponseBody,
    {} as IPostArticleRevisionRequestBody,
);

// Usable action for getting an article
function postArticle(requestOptions: IPostArticleRequestBody) {
    return apiThunk("post", `/articles`, postArticleActions, requestOptions);
}

// Usable action for getting an article
function postRevision(requestOptions: IPostArticleRevisionRequestBody) {
    return apiThunk("post", `/articles`, postRevisionActions, requestOptions);
}

// Actions made for components to use.
export const componentActions = {
    postArticle,
    postRevision,
};

// Actions exposed purely for testing purposes.
// You probably should not be using them yourself.
export const _rawApiActions = {
    postArticleActions,
    postRevisionActions,
};

export type ActionTypes = ActionsUnion<typeof postArticleActions | typeof postRevisionActions>;
