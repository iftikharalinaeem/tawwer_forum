/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IKbNavigationRequest } from "@knowledge/@types/api";
import { getKbNavigationActions, setNavigatedCategory } from "./actions";
import { apiThunk } from "@library/state/utility";

/**
 * Get location data from the server.
 *
 * @param options
 */
export function getKbNavigation(options: IKbNavigationRequest) {
    return apiThunk(
        "get",
        `/knowledge-navigation?knowledgeCategoryID=${options.knowledgeCategoryID}`,
        getKbNavigationActions,
        options,
    );
}

/**
 * Navigate to a particular category.
 *
 * Immediately navigates in one level, then requests the data for the next level deeper.
 *
 * @param categoryID
 */
export function navigateToCategory(categoryID: number) {
    return async dispatch => {
        dispatch(setNavigatedCategory(categoryID));
        dispatch(getKbNavigation({ knowledgeCategoryID: categoryID }));
    };
}
