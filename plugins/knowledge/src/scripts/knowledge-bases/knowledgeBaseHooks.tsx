/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import {
    KnowledgeBaseStatus,
    IKnowledgeBase,
    IKnowledgeBaseFragment,
} from "@knowledge/knowledge-bases/KnowledgeBaseModel";
import { useSelector } from "react-redux";
import { IKnowledgeAppStoreState } from "@knowledge/state/model";
import { useKnowledgeBaseActions } from "@knowledge/knowledge-bases/KnowledgeBaseActions";
import { useEffect, useDebugValue } from "react";
import { LoadStatus, ILoadable, ILinkListData } from "@vanilla/library/src/scripts/@types/api/core";
import { useNavigationActions } from "@knowledge/navigation/state/NavigationActions";
import NavigationSelector from "@knowledge/navigation/state/NavigationSelector";

export function useKnowledgeBases(status: KnowledgeBaseStatus) {
    const { knowledgeBasesByID } = useSelector((state: IKnowledgeAppStoreState) => state.knowledge.knowledgeBases);
    const { getAll } = useKnowledgeBaseActions();

    useEffect(() => {
        if (knowledgeBasesByID.status === LoadStatus.PENDING) {
            getAll({ status });
        }
    }, [knowledgeBasesByID, getAll, status]);

    return knowledgeBasesByID;
}

export function useKnowledgeBase(kbID: number): ILoadable<IKnowledgeBase> {
    const { getSingleKB } = useKnowledgeBaseActions();
    const kbs = useKnowledgeBases(KnowledgeBaseStatus.PUBLISHED);
    const { status, error } = useSelector((state: IKnowledgeAppStoreState) => {
        return (
            state.knowledge.knowledgeBases.getStatusesByID[kbID] ?? {
                status: LoadStatus.PENDING,
            }
        );
    });

    useEffect(() => {
        if ([LoadStatus.PENDING, LoadStatus.LOADING].includes(status)) {
            void getSingleKB({ kbID });
        }
    }, [kbs, kbID, status, getSingleKB]);

    return {
        data: kbs.data?.[kbID],
        status,
        error,
    };
}

export function useUniversalSources(kbID?: number): IKnowledgeBaseFragment[] {
    const universalSources = useSelector((state: IKnowledgeAppStoreState) => {
        if (!kbID) {
            return [];
        }
        const kbData = state.knowledge.knowledgeBases.knowledgeBasesByID.data;
        if (kbData) {
            const sources: IKnowledgeBaseFragment[] = [];
            for (const knowledgeBase of Object.values(kbData)) {
                if (knowledgeBase.isUniversalSource && knowledgeBase.universalTargetIDs.includes(kbID)) {
                    sources.push(knowledgeBase);
                }
            }
            return sources;
        } else {
            return [];
        }
    });
    return universalSources;
}

export function useAllowedUniversalTargets(kbID?: number) {
    const knowledgeBases = useKnowledgeBases(KnowledgeBaseStatus.PUBLISHED);
    if (!knowledgeBases.data) {
        return [];
    }

    const allKBs = Object.values(knowledgeBases.data);

    const allowedKBs = allKBs.filter(kb => {
        if (kbID !== undefined && kb.knowledgeBaseID === kbID) {
            return false;
        }

        if (kb.isUniversalSource) {
            return false;
        }
        return true;
    });
    return allowedKBs;
}

export function useKBData() {
    return useSelector((state: IKnowledgeAppStoreState) => state.knowledge.knowledgeBases);
}

export function useHelpCenterNavigation(
    knowledgeBaseID: number,
): ILoadable<{ navigation: ILinkListData; rootCategoryUrl: string }> {
    const { getNavigationFlat } = useNavigationActions();
    const knowledgeBase = useKnowledgeBase(knowledgeBaseID);
    const data = useSelector((state: IKnowledgeAppStoreState) => {
        if (!knowledgeBase.data) {
            return {
                ...knowledgeBase,
                data: undefined,
            };
        }
        const knowledgeState = state.knowledge.navigation;
        const loadStatus = knowledgeState.fetchStatusesByKbID[knowledgeBaseID] || LoadStatus.PENDING;

        if (loadStatus === LoadStatus.SUCCESS) {
            const navigation = NavigationSelector.selectHelpCenterHome(
                knowledgeState.navigationItems,
                knowledgeBase.data,
            );
            const rootCategory = NavigationSelector.selectCategory(
                knowledgeBase.data.rootCategoryID,
                knowledgeState.navigationItems,
            )!;

            return {
                status: LoadStatus.SUCCESS,
                data: {
                    navigation,
                    rootCategoryUrl: rootCategory.url,
                },
            };
        } else {
            return {
                status: loadStatus,
            };
        }
    });

    const { status } = data;
    useEffect(() => {
        if (status === LoadStatus.PENDING) {
            void getNavigationFlat(knowledgeBaseID);
        }
    }, [knowledgeBaseID, status, getNavigationFlat]);

    useDebugValue(data);
    return data;
}
