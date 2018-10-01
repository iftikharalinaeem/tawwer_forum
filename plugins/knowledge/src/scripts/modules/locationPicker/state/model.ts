/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IKbCategoryFragment } from "@knowledge/@types/api";
import { model as categoryModel } from "@knowledge/modules/categories/state";
import { IStoreState } from "@knowledge/state/model";

export function getCurrentLocationBreadcrumb(state: IStoreState): IKbCategoryFragment[] {
    const { locationPicker } = state.knowledge;
    return categoryModel.getKbCategoryBreadcrumb(state, locationPicker.currentCategoryID);
}
