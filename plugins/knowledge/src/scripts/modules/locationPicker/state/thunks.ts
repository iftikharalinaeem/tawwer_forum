/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IKbNavigationRequest } from "@knowledge/@types/api";
import { getKbNavigationActions, setCategory } from "./actions";
import { apiThunk } from "@library/state/utility";

export function getKbNavigation(options: IKbNavigationRequest) {
    return apiThunk(
        "get",
        `/knowledge-navigation?knowledgeCategoryID=${options.knowledgeCategoryID}`,
        getKbNavigationActions,
        options,
    );
}

export function navigateToCategory(categoryID: number) {
    return async dispatch => {
        dispatch(setCategory(categoryID));
        dispatch(getKbNavigation({ knowledgeCategoryID: categoryID }));
    };
}
