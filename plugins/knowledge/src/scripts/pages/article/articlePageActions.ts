/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IGetArticleResponseBody } from "@knowledge/@types/api";
import { generateApiActionCreators, ActionsUnion, apiThunk, createAction } from "@library/state/utility";

export const RESET_PAGE_STATE = "RESET_ARTICLE_PAGE_STATE";

// Non-api related actions for the page.
const nonApiActions = {
    clearArticlePageState: () => createAction(RESET_PAGE_STATE),
};

// Actions made for components to use.
export const componentActions = {
    ...nonApiActions,
};

export type ActionTypes = ActionsUnion<typeof nonApiActions>;
