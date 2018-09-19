/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import { generateApiActionCreators, ActionsUnion, apiThunk, createAction } from "@library/state/utility";
import {
    IPostArticleRevisionRequestBody,
    IPostArticleRevisionResponseBody,
    IGetArticleRevisionResponseBody,
    IGetArticleRevisionRequestBody,
} from "@knowledge/@types/api";

///
/// POST /article-revision
///

export const POST_REVISION_REQUEST = "POST_REVISION_REQUEST";
export const POST_REVISION_RESPONSE = "POST_REVISION_RESPONSE";
export const POST_REVISION_ERROR = "POST_REVISION_ERROR";

const postRevisionActions = generateApiActionCreators(
    POST_REVISION_REQUEST,
    POST_REVISION_RESPONSE,
    POST_REVISION_ERROR,
    // https://github.com/Microsoft/TypeScript/issues/10571#issuecomment-345402872
    {} as IPostArticleRevisionResponseBody,
    {} as IPostArticleRevisionRequestBody,
);

// Usable action for getting an article
function postRevision(requestOptions: IPostArticleRevisionRequestBody) {
    return apiThunk("post", `/article-revisions`, postRevisionActions, requestOptions);
}

///
/// GET /article-revision/:id
///

export const GET_REVISION_REQUEST = "GET_REVISION_REQUEST";
export const GET_REVISION_RESPONSE = "GET_REVISION_RESPONSE";
export const GET_REVISION_ERROR = "GET_REVISION_ERROR";

const getRevisionActions = generateApiActionCreators(
    GET_REVISION_REQUEST,
    GET_REVISION_RESPONSE,
    GET_REVISION_ERROR,
    // https://github.com/Microsoft/TypeScript/issues/10571#issuecomment-345402872
    {} as IGetArticleRevisionResponseBody,
    {} as IGetArticleRevisionRequestBody,
);

// Usable action for getting an article
function getRevision(revisionID: number) {
    return apiThunk("get", `/article-revisions/${revisionID}`, postRevisionActions, {});
}

// Actions made for components to use.
export const componentActions = {
    postRevision,
    getRevision,
};

// Actions exposed purely for testing purposes.
// You probably should not be using them yourself.
export const _rawApiActions = {
    postRevisionActions,
    getRevisionActions,
};

export type ActionTypes = ActionsUnion<typeof postRevisionActions | typeof getRevisionActions>;
