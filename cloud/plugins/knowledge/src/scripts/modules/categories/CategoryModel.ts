/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import CategoryActions from "@knowledge/modules/categories/CategoryActions";
import ReduxReducer from "@library/redux/ReduxReducer";
import produce from "immer";
import { reducerWithInitialState } from "typescript-fsa-reducers";
import { IKbCategory } from "@knowledge/@types/api/kbCategory";
import { KNOWLEDGE_ACTION } from "@knowledge/state/model";

export interface IKbCategoriesState {
    categoriesByID: {
        [id: number]: IKbCategory;
    };
}

export default class CategoryModel implements ReduxReducer<IKbCategoriesState> {
    public static INITIAL_STATE: IKbCategoriesState = {
        categoriesByID: {},
    };

    public reducer = produce(
        reducerWithInitialState(CategoryModel.INITIAL_STATE)
            .case(CategoryActions.getCategoryACs.done, (nextState, payload) => {
                nextState.categoriesByID[payload.params.id] = payload.result;
                return nextState;
            })
            .default((nextState, action: KNOWLEDGE_ACTION) => {
                // Default for non-FSA actions.
                switch (action.type) {
                    case CategoryActions.PATCH_CATEGORY_RESPONSE: {
                        const { knowledgeCategoryID } = action.meta;
                        const category = nextState.categoriesByID[knowledgeCategoryID];
                        if (category) {
                            nextState.categoriesByID[knowledgeCategoryID] = {
                                ...category,
                                ...action.payload.data,
                            };
                        }
                        break;
                    }
                }

                return nextState;
            }),
    );
}
