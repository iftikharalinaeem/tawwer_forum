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

export const getArticleActions = generateApiActionCreators(
    constants.GET_ARTICLE_REQUEST,
    constants.GET_ARTICLE_RESPONSE,
    constants.GET_ARTICLE_ERROR,
    // https://github.com/Microsoft/TypeScript/issues/10571#issuecomment-345402872
    {} as IPostArticleResponseBody,
    {} as any,
);

export const postArticleActions = generateApiActionCreators(
    constants.POST_ARTICLE_REQUEST,
    constants.POST_ARTICLE_RESPONSE,
    constants.POST_ARTICLE_ERROR,
    // https://github.com/Microsoft/TypeScript/issues/10571#issuecomment-345402872
    {} as IPostArticleResponseBody,
    {} as IPostArticleRequestBody,
);

export const getRevisionActions = generateApiActionCreators(
    constants.GET_REVISION_REQUEST,
    constants.GET_REVISION_RESPONSE,
    constants.GET_REVISION_ERROR,
    // https://github.com/Microsoft/TypeScript/issues/10571#issuecomment-345402872
    {} as IGetArticleRevisionResponseBody,
    {} as IGetArticleRevisionRequestBody,
);

export const postRevisionActions = generateApiActionCreators(
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
    | ActionsUnion<typeof postRevisionActions>
    | ActionsUnion<typeof postArticleActions>
    | ActionsUnion<typeof getRevisionActions>
    | ActionsUnion<typeof getArticleActions>
    | ReturnType<typeof clearPageState>;
