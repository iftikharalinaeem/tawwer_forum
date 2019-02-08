/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import ReduxReducer from "@library/state/ReduxReducer";
import { KnowledgeReducer, KNOWLEDGE_ACTION } from "@knowledge/state/model";
import { IKbCategoryFragment, IKbCategory } from "@knowledge/@types/api";
import produce from "immer";
import CategoryActions from "@knowledge/modules/categories/CategoryActions";
import { reducerWithoutInitialState, reducerWithInitialState } from "typescript-fsa-reducers";
import { stat } from "fs";

export interface IKbCategoriesState {
    categoriesByID: {
        [id: number]: IKbCategory;
    };
}

export default class CategoryModel implements ReduxReducer<IKbCategoriesState> {
    public initialState: IKbCategoriesState = {
        categoriesByID: {},
    };

    public reducer = produce(
        reducerWithInitialState(this.initialState)
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
