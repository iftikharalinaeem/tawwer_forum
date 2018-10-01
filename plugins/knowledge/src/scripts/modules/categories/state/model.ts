/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IKbCategoryFragment, IKbCategoryMultiTypeFragment } from "@knowledge/@types/api";
import { IStoreState } from "@knowledge/state/model";

export function getKbCategory(state: IStoreState, id: number): IKbCategoryFragment {
    return state.knowledge.categories.categoriesByID[id];
}

export function getKbCategoryMixedRecord(state: IStoreState, id: string): IKbCategoryMultiTypeFragment {
    const { knowledgeCategoryID, ...rest } = state.knowledge.categories[id];
    return {
        ...rest,
        recordType: "knowledgeCategory",
        recordID: knowledgeCategoryID,
    };
}

export function getKbCategoryBreadcrumb(state: IStoreState, id: number): IKbCategoryFragment[] {
    let crumbs: IKbCategoryFragment[] = [];
    let complete = false;
    let lookupID = id;

    while (!complete) {
        const category = getKbCategory(state, lookupID);
        if (!category) {
            throw new Error("Attempting to lookup breadcrumb that doesn't exist.");
        }

        crumbs.push(category);
        if (category.parentID === -1) {
            complete = true;
        } else {
            lookupID = category.parentID;
        }
    }

    return crumbs;
}
