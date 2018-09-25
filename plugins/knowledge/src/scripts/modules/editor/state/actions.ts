/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inconstants.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import * as constants from "./constants";
import {
    IPostArticleResponseBody,
    IPostArticleRequestBody,
    IPostArticleRevisionResponseBody,
    IPostArticleRevisionRequestBody,
    IGetArticleRevisionResponseBody,
    IGetArticleRevisionRequestBody,
} from "@knowledge/@types/api";
import { generateApiActionCreators, createAction, ActionsUnion } from "@library/state/utility";

///
/// Simple actions
///

export const getArticle = generateApiActionCreators(
    constants.GET_ARTICLE_REQUEST,
    constants.GET_ARTICLE_RESPONSE,
    constants.GET_ARTICLE_ERROR,
    // https://github.com/Microsoft/TypeScript/issues/10571#issuecomment-345402872
    {} as IPostArticleResponseBody,
    {} as any,
);

export const postArticle = generateApiActionCreators(
    constants.POST_ARTICLE_REQUEST,
    constants.POST_ARTICLE_RESPONSE,
    constants.POST_ARTICLE_ERROR,
    // https://github.com/Microsoft/TypeScript/issues/10571#issuecomment-345402872
    {} as IPostArticleResponseBody,
    {} as IPostArticleRequestBody,
);

export const getRevision = generateApiActionCreators(
    constants.GET_REVISION_REQUEST,
    constants.GET_REVISION_RESPONSE,
    constants.GET_REVISION_ERROR,
    // https://github.com/Microsoft/TypeScript/issues/10571#issuecomment-345402872
    {} as IGetArticleRevisionResponseBody,
    {} as IGetArticleRevisionRequestBody,
);

export const postRevision = generateApiActionCreators(
    constants.POST_REVISION_REQUEST,
    constants.POST_REVISION_RESPONSE,
    constants.POST_REVISION_ERROR,
    // https://github.com/Microsoft/TypeScript/issues/10571#issuecomment-345402872
    {} as IPostArticleRevisionResponseBody,
    {} as IPostArticleRevisionRequestBody,
);

export function clearPageState() {
    return createAction(constants.CLEAR_PAGE_STATE);
}

export type ActionTypes =
    | ActionsUnion<typeof postRevision>
    | ActionsUnion<typeof getRevision>
    | ActionsUnion<typeof postArticle>
    | ReturnType<typeof clearPageState>;
