/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IKbCategoryFragment } from "@knowledge/@types/api";
import LocationPickerActions from "@knowledge/modules/locationPicker/LocationPickerActions";
import { IKbNavigationItem, KbRecordType } from "@knowledge/navigation/state/NavigationModel";
import NavigationSelector from "@knowledge/navigation/state/NavigationSelector";
import { IStoreState, KnowledgeReducer } from "@knowledge/state/model";
import { INavigationTreeItem } from "@library/@types/api";
import { t } from "@library/application";
import ReduxReducer from "@library/state/ReduxReducer";
import produce from "immer";
import { createSelector } from "reselect";
import { reducerWithoutInitialState } from "typescript-fsa-reducers";

export interface ILPConnectedData extends ILocationPickerState {
    pageContents: INavigationTreeItem[];
    navigatedCategory: IKbCategoryFragment | null; // What page the user is on in the picker.
    selectedCategory: IKbCategoryFragment | null; // What category is selected (still not chosen).
    choosenCategory: IKbCategoryFragment | null; // What category is chosen (input closes after a selection)
}

/**
 * Reducer for the article page.
 */
export default class LocationPickerModel extends ReduxReducer<ILocationPickerState> {
    private static selectState = (state: IStoreState) => state;
    private static stateSlice = (state: IStoreState) => state.knowledge.locationPicker;
    public static selectPageContents = createSelector(
        LocationPickerModel.selectState,
        (state): IKbNavigationItem[] => {
            return [];
            // return CategoryModel.selectMixedRecordTree(state, navID).children!;
        },
    );

    public static selectParentRecord = createSelector(
        LocationPickerModel.stateSlice,
        NavigationSelector.selectNavigationItems,
        (lpState, navItems): ILocationPickerRecord | null => {
            const { navigatedRecord } = lpState;
            if (!navigatedRecord) {
                return null;
            }

            // Check if we have a knowledgeBase parent.
            const fullNavItem = navItems[navigatedRecord.recordType + navigatedRecord.recordID];
            if (!fullNavItem) {
                return null;
            }

            const fullParent = navItems[KbRecordType.CATEGORY + fullNavItem.parentID];
            if (fullParent) {
                return fullParent;
            }

            return null;
        },
    );

    public static selectNavigatedTitle = createSelector(
        LocationPickerModel.stateSlice,
        NavigationSelector.selectNavigationItems,
        (lpState, navItems): string => {
            const { navigatedRecord } = lpState;
            if (!navigatedRecord) {
                return t("Knowledge Bases");
            }

            // Check if we have a knowledgeBase parent.
            const fullNavItem = navItems[navigatedRecord.recordType + navigatedRecord.recordID];
            if (!fullNavItem) {
                return t("Knowledge Bases");
            }

            return fullNavItem.name;
        },
    );

    public initialState: ILocationPickerState = {
        selectedRecord: null,
        navigatedRecord: null,
        chosenRecord: null,
    };

    /**
     * @inheritDoc
     */
    public reducer: ReducerType = (state = this.initialState, action) => {
        return produce(state, nextState => this.reduceSelf(nextState, action));
    };

    public reduceSelf: ReducerType = reducerWithoutInitialState<ILocationPickerState>()
        .case(LocationPickerActions.initAC, (state, payload) => {
            const { selected, parent } = payload;
            state.navigatedRecord = parent;
            state.selectedRecord = selected;
            state.chosenRecord = selected;
            return state;
        })
        .case(LocationPickerActions.chooseAC, (state, payload) => {
            state.chosenRecord = payload;
            return state;
        })
        .case(LocationPickerActions.selectAC, (state, payload) => {
            state.selectedRecord = payload;
            return state;
        })
        .case(LocationPickerActions.navigateAC, (state, payload) => {
            state.navigatedRecord = payload;
            return state;
        });
}

export interface ILocationPickerRecord {
    recordType: KbRecordType;
    recordID: number;
    knowledgeBaseID: number;
}

export interface ILocationPickerState {
    navigatedRecord: ILocationPickerRecord | null; // What page the user is on in the picker.
    selectedRecord: ILocationPickerRecord | null; // What category is selected (still not chosen).
    chosenRecord: ILocationPickerRecord | null; // What category is chosen (input closes after a selection)
}

export type ReducerType = KnowledgeReducer<ILocationPickerState>;
