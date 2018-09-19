/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import { ActionsUnion, createAction } from "@library/state/utility";

export const CLEAR_EDITOR_PAGE_STATE = "CLEAR_EDITOR_PAGE_STATE";

// Non-api related actions for the page.
const nonApiActions = {
    clearEditorPageState: () => createAction(CLEAR_EDITOR_PAGE_STATE),
};

// Actions made for components to use.
export const componentActions = {
    ...nonApiActions,
};

export type ActionTypes = ActionsUnion<typeof nonApiActions>;
