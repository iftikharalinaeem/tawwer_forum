/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import ReduxReducer from "@library/redux/ReduxReducer";
import { KnowledgeReducer, IKnowledgeAppStoreState } from "@knowledge/state/model";
import { reducerWithoutInitialState } from "typescript-fsa-reducers";
import produce from "immer";
import KnowledgeBaseActions, { useKnowledgeBaseActions } from "@knowledge/knowledge-bases/KnowledgeBaseActions";
import { createSelector } from "reselect";
import { IKbNavigationItem, KbRecordType } from "@knowledge/navigation/state/NavigationModel";
import { LoadStatus, ILoadable } from "@library/@types/api/core";
import { useSelector } from "react-redux";
import { useEffect } from "react";

/**
 * Model for working with actions & data related to the /api/v2/knowledge-bases endpoint.
 */
export default class KnowledgeBaseModel implements ReduxReducer<IKnowledgeBasesState> {
    /**
     * Selector for our own state.
     */
    private static selectSelf = (state: IKnowledgeAppStoreState) => state.knowledge.knowledgeBases;

    /**
     * Selector for a list of loaded knowledge bases.
     */
    public static selectKnowledgeBases = createSelector([KnowledgeBaseModel.selectSelf], selfState =>
        Object.values(selfState.knowledgeBasesByID.data || {}),
    );

    public static selectKnowledgeBasesAsNavItems = createSelector(
        [KnowledgeBaseModel.selectKnowledgeBases],
        (kbs): IKbNavigationItem[] => {
            return kbs.map(kb => {
                const navItem: IKbNavigationItem = {
                    recordType: KbRecordType.KB,
                    recordID: kb.knowledgeBaseID,
                    knowledgeBaseID: kb.knowledgeBaseID,
                    name: kb.name,
                    url: kb.url,
                    parentID: -1,
                    sort: null,
                };
                return navItem;
            });
        },
    );

    public static selectByUrlCode = (state: IKnowledgeAppStoreState, urlCode: string) => {
        // We could index these by urlCode as well, but right now it doesn't seem necessary.
        const selfState = KnowledgeBaseModel.selectKnowledgeBases(state);
        return selfState.find(kb => kb.urlCode === urlCode) || null;
    };

    public static INITIAL_STATE: IKnowledgeBasesState = {
        knowledgeBasesByID: {
            status: LoadStatus.PENDING,
        },
    };

    public initialState = KnowledgeBaseModel.INITIAL_STATE;
    public reducer: ReducerType = (state = this.initialState, action) => {
        return produce(state, nextState => {
            return this.internalReducer(nextState, action);
        });
    };

    /**
     * Reducer factory for knowledge base items.
     */
    private internalReducer = reducerWithoutInitialState<IKnowledgeBasesState>()
        .case(KnowledgeBaseActions.GET_ACS.started, state => {
            state.knowledgeBasesByID.status = LoadStatus.LOADING;
            return state;
        })
        .case(KnowledgeBaseActions.GET_ACS.done, (state, payload) => {
            const normalized: { [id: number]: IKnowledgeBase } = {};
            for (const kb of payload.result) {
                normalized[kb.knowledgeBaseID] = kb;
            }
            state.knowledgeBasesByID.status = LoadStatus.SUCCESS;
            state.knowledgeBasesByID.data = normalized;
            return state;
        })
        .case(KnowledgeBaseActions.GET_ACS.failed, (state, action) => {
            state.knowledgeBasesByID.error = action.error;
            return state;
        });
}

export interface IKnowledgeBasesState {
    knowledgeBasesByID: ILoadable<{
        [id: number]: IKnowledgeBase;
    }>;
}

export enum KbViewType {
    HELP = "help",
    GUIDE = "guide",
}

export enum KnowledgeBaseSortMode {
    MANUAL = "manual",
    NAME = "name",
    DATE_INSERTED = "dateInserted",
    DATE_INSERTED_DESC = "dateInsertedDesc",
}

interface ISiteSection {
    basePath: string;
    contentLocale: string;
    sectionGroup: string;
    sectionID: string;
    name: string;
}

export enum KnowledgeBaseStatus {
    DELETED = "deleted",
    PUBLISHED = "published",
}

/**
 * Interface representing a knowledge base resource.
 */
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
    status: KnowledgeBaseStatus;
    bannerImage: string;
    sourceLocale: string;
    viewType: KbViewType;
    rootCategoryID: number;
    defaultArticleID: number | null;
    siteSections: ISiteSection[];
}

type ReducerType = KnowledgeReducer<IKnowledgeBasesState>;

export function useKnowledgeBases(status: KnowledgeBaseStatus) {
    const { knowledgeBasesByID } = useSelector((state: IKnowledgeAppStoreState) => state.knowledge.knowledgeBases);
    const { getAll } = useKnowledgeBaseActions();

    useEffect(() => {
        if (knowledgeBasesByID.status === LoadStatus.PENDING) {
            getAll(status);
        }
    }, [knowledgeBasesByID, getAll]);

    return knowledgeBasesByID;
}
