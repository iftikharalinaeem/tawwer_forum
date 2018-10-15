/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import { LoadStatus, ILoadable } from "@library/@types/api";
import ReduxReducer from "@library/state/ReduxReducer";
import {
    IKbCategoryFragment,
    IKbCategoryMultiTypeFragment,
    IKbNavigationItem,
    IKbNavigationCategory,
} from "@knowledge/@types/api";
import CategoryActions from "@knowledge/modules/categories/CategoryActions";
import { IStoreState } from "@knowledge/state/model";

export type IKbCategoriesState = ILoadable<{
    categoriesByID: {
        [id: number]: IKbCategoryFragment;
    };
}>;

export default class CategoryModel implements ReduxReducer<IKbCategoriesState> {
    /**
     * Get a category out of the state as a category fragment.
     *
     * @param state - The top level redux state.
     * @param categoryID - The ID of the category to lookup.
     */
    public static selectKbCategoryFragment(state: IStoreState, categoryID: number): IKbCategoryFragment {
        if (state.knowledge.categories.status !== LoadStatus.SUCCESS) {
            throw new Error("Categories not loaded.");
        }

        const category = state.knowledge.categories.data.categoriesByID[categoryID];

        if (category === undefined) {
            throw new Error(`Category ${categoryID} not found.`);
        }

        return category;
    }

    /**
     * Get a category out of the state as a MutliTypeFragment.
     *
     * @param state - The top level redux state.
     * @param categoryID - The ID of the category to lookup.
     */
    public static selectMixedRecord(state: IStoreState, categoryID: number): IKbCategoryMultiTypeFragment {
        const { knowledgeCategoryID, ...rest } = this.selectKbCategoryFragment(state, categoryID);
        return {
            ...rest,
            recordType: "knowledgeCategory",
            recordID: knowledgeCategoryID,
        };
    }

    /**
     *
     * @param state
     * @param parentID
     */
    public static selectChildrenIDsFromParent(state: IStoreState, parentID: number): number[] {
        return Object.values(state.knowledge.categories.data!.categoriesByID)
            .filter(category => category.parentID === parentID)
            .map(category => category.knowledgeCategoryID);
    }

    public static selectMixedRecordTree(state: IStoreState, categoryID: number, maxDepth = 2): IKbNavigationCategory {
        const category: IKbNavigationCategory = this.selectMixedRecord(state, categoryID);

        if (maxDepth > 1) {
            category.children = this.selectChildrenIDsFromParent(state, categoryID).map(id =>
                this.selectMixedRecordTree(state, id, maxDepth - 1),
            );
        }
        return category;
    }

    /**
     * Follow a category's parentID up to the root to assemble it's breadcrumbs.
     *
     * @param state - The top level redux state.
     * @param categoryID - The ID of the category to lookup.
     */
    public static selectKbCategoryBreadcrumb(state: IStoreState, categoryID: number): IKbCategoryFragment[] {
        const crumbs: IKbCategoryFragment[] = [];
        let complete = false;
        let lookupID = categoryID;

        // Loop through and follow up the parent IDs.
        while (!complete) {
            const category = CategoryModel.selectKbCategoryFragment(state, lookupID);
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

    public initialState: IKbCategoriesState = {
        status: LoadStatus.PENDING,
    };

    public reducer = (
        state: IKbCategoriesState = this.initialState,
        action: typeof CategoryActions.ACTION_TYPES,
    ): IKbCategoriesState => {
        switch (action.type) {
            case CategoryActions.GET_ALL_REQUEST:
                return {
                    status: LoadStatus.LOADING,
                };
            case CategoryActions.GET_ALL_RESPONSE:
                const categories = {};
                for (const category of action.payload.data) {
                    categories[category.knowledgeCategoryID] = category;
                }

                return {
                    status: LoadStatus.SUCCESS,
                    data: {
                        categoriesByID: categories,
                    },
                };
            case CategoryActions.GET_ALL_ERROR:
                return {
                    status: LoadStatus.ERROR,
                    error: action.payload,
                };
        }

        return state;
    };
}
