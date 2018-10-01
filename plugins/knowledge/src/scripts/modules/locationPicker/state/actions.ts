/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import * as constants from "./constants";
import { IKbNavigationRequest, IKbNavigationResponse } from "@knowledge/@types/api";
import { generateApiActionCreators, createAction, ActionsUnion } from "@library/state/utility";

export const getKbNavigationActions = generateApiActionCreators(
    constants.GET_KB_NAVIGATION_REQUEST,
    constants.GET_KB_NAVIGATION_RESPONSE,
    constants.GET_KB_NAVIGATION_ERROR,
    // https://github.com/Microsoft/TypeScript/issues/10571#issuecomment-345402872
    {} as IKbNavigationResponse,
    {} as IKbNavigationRequest,
);

export function resetNavigation() {
    return createAction(constants.RESET_NAVIGATION);
}

export function navigateToCategory(categoryID: number) {
    return createAction(constants.NAVIGATE_TO_CATEGORY, { categoryID });
}

export type ActionTypes =
    | ActionsUnion<typeof getKbNavigationActions>
    | ReturnType<typeof resetNavigation>
    | ReturnType<typeof navigateToCategory>;
