/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import ReduxReducer from "@library/state/ReduxReducer";
import { KnowledgeReducer } from "@knowledge/state/model";

export default class KnowledgeBaseModel extends ReduxReducer<IKnowledgeBasesState> {
    public initialState: IKnowledgeBasesState = {};

    public reducer: ReducerType = (state = this.initialState, action) => {
        return state;
    };
}

export interface IKnowledgeBasesState {}

export enum KnowledgeBaseDisplayType {
    HELP = "help",
    GUIDE = "guide",
}

export enum KnowledgeBaseSortMode {
    MANUAL = "manual",
    NAME = "name",
    DATE_INSERTED = "dateInserted",
    DATE_INSERTED_DESC = "dateInsertedDesc",
}

export interface IKnowledgeBase {
    knowledgeBaseID: number;
    name: string;
    description: string;
    sortArticles: KnowledgeBaseSortMode;
    insertUserID: number;
    dateInserted: string;
    updateUserID: number;
    dateUpdated: string;
    countArticles: number;
    countCategories: number;
    urlCode: string;
    url: string;
    icon: string;
    sourceLocale: string;
    viewType: KnowledgeBaseDisplayType;
    rootCategoryID: number;
}

type ReducerType = KnowledgeReducer<IKnowledgeBasesState>;
