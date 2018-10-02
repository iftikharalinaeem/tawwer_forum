/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IKbCategoryFragment, IKbCategoryMultiTypeFragment } from "@knowledge/@types/api";
import { IStoreState } from "@knowledge/state/model";

/**
 * Get a category out of the state as a category fragment.
 *
 * @param state - The top level redux state.
 * @param categoryID - The ID of the category to lookup.
 */
export function selectKbCategoryFragment(state: IStoreState, categoryID: number): IKbCategoryFragment {
    return state.knowledge.categories.categoriesByID[categoryID];
}

/**
 * Get a category out of the state as a MutliTypeFragment.
 *
 * @param state - The top level redux state.
 * @param categoryID - The ID of the category to lookup.
 */
export function selectKbCategoryMixedRecord(state: IStoreState, categoryID: string): IKbCategoryMultiTypeFragment {
    const { knowledgeCategoryID, ...rest } = state.knowledge.categories[categoryID];
    return {
        ...rest,
        recordType: "knowledgeCategory",
        recordID: knowledgeCategoryID,
    };
}

/**
 * Follow a category's parentID up to the root to assemble it's breadcrumbs.
 *
 * @param state - The top level redux state.
 * @param categoryID - The ID of the category to lookup.
 */
export function selectKbCategoryBreadcrumb(state: IStoreState, categoryID: number): IKbCategoryFragment[] {
    const crumbs: IKbCategoryFragment[] = [];
    let complete = false;
    let lookupID = categoryID;

    // Loop through and follow up the parent IDs.
    while (!complete) {
        const category = selectKbCategoryFragment(state, lookupID);
        if (!category) {
            throw new Error("Attempting to lookup breadcrumb that doesn't exist.");
        }

        crumbs.unshift(category);
        if (category.parentID === -1) {
            complete = true;
        } else {
            lookupID = category.parentID;
        }
    }

    return crumbs;
}
