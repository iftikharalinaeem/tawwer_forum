/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import { createAction, generateApiActionCreators, ActionsUnion } from "@library/state/utility";
import { IGetArticleResponseBody } from "@knowledge/@types/api";
import * as constants from "./constants";

export function clearPageState() {
    return createAction(constants.RESET_PAGE_STATE);
}

// Raw actions for getting an article
export const getArticleActions = generateApiActionCreators(
    constants.GET_ARTICLE_REQUEST,
    constants.GET_ARTICLE_RESPONSE,
    constants.GET_ARTICLE_ERROR,
    // https://github.com/Microsoft/TypeScript/issues/10571#issuecomment-345402872
    {} as IGetArticleResponseBody,
    {},
);

export type ActionTypes = ActionsUnion<typeof getArticleActions> | ReturnType<typeof clearPageState>;
