/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { IKbNavigationItem, IPatchFlatItem } from "@knowledge/navigation/state/NavigationModel";
import { IKnowledgeAppStoreState } from "@knowledge/state/model";
import { IApiError, LoadStatus } from "@library/@types/api/core";
import ReduxActions, { bindThunkAction } from "@library/redux/ReduxActions";
import uniqueId from "lodash/uniqueId";
import { actionCreatorFactory } from "typescript-fsa";
import { getCurrentLocale } from "@vanilla/i18n";
import { useDispatch } from "react-redux";
const createAction = actionCreatorFactory("@@navigation");
import { useMemo, useCallback } from "react";
import apiv2 from "@library/apiv2";
import { getOnlyTranslated } from "@knowledge/state/getOnlyTranslated";

/**
 * Redux actions for knowledge base navigation data.
 */
export default class NavigationActions extends ReduxActions<IKnowledgeAppStoreState> {
    public static getTranslationSourceNavigationItemsACs = createAction.async<
        { knowledgeBaseID: number },
        IKbNavigationItem[],
        IApiError
    >("GET_TRANSLATIONSOURCE_NAVIGATION_ITEMS");

    public getTranslationSourceNavigationItems = async (knowledgeBaseID: number) => {
        const kbsByID = this.getState().knowledge.knowledgeBases.knowledgeBasesByID;
        let sourceLocale = "";
        if (kbsByID.data) {
            sourceLocale = kbsByID.data[knowledgeBaseID].sourceLocale;
        }

        const apiThunk = bindThunkAction(NavigationActions.getTranslationSourceNavigationItemsACs, async () => {
            const response = await this.api.get(
                `/knowledge-bases/${knowledgeBaseID}/navigation-flat?locale=${sourceLocale}`,
            );

            return response.data;
        })({ knowledgeBaseID });

        return await this.dispatch(apiThunk);
    };

    public static getNavigationFlatACs = createAction.async<
        { knowledgeBaseID: number },
        IKbNavigationItem[],
        IApiError
    >("GET_NAVIGATION_FLAT");
    /**
     * Get navigation for a knowledge base in the flat format.
     *
     * @param options Parameters for the request.
     */
    public getNavigationFlat = async (knowledgeBaseID: number, forceUpdate = false) => {
        const state = this.getState();

        const fetchStatus = state.knowledge.navigation.fetchStatusesByKbID[knowledgeBaseID];

        if ([LoadStatus.SUCCESS, LoadStatus.LOADING].includes(fetchStatus) && !forceUpdate) {
            return;
        }

        const apiThunk = bindThunkAction(NavigationActions.getNavigationFlatACs, async () => {
            const locale = getCurrentLocale();
            const response = await this.api.get(`/knowledge-bases/${knowledgeBaseID}/navigation-flat`, {
                params: { locale, "only-translated": getOnlyTranslated() },
            });
            return response.data;
        })({ knowledgeBaseID });

        return await this.dispatch(apiThunk);
    };

    public static patchNavigationFlatACs = createAction.async<
        { knowledgeBaseID: number; patchItems: IPatchFlatItem[]; transactionID: string },
        IKbNavigationItem[],
        IApiError
    >("PATCH_NAVIGATION_FLAT");

    public static markRetryAsLoading = createAction("MARK_RETRY_AS_LOADING");
    public markRetryAsLoading = this.bindDispatch(NavigationActions.markRetryAsLoading);

    public static clearErrors = createAction("CLEAR_ERRORS");
    public clearErrors = this.bindDispatch(NavigationActions.clearErrors);

    public static setPatchItems = createAction<IPatchFlatItem[]>("SET_PATCH_ITEMS");
    public setPatchItems = this.bindDispatch(NavigationActions.setPatchItems);

    /**
     * Patch a knowlege base's navigation using the flat format.
     *
     * @param patchItems Patch request parameters.
     */
    public patchNavigationFlat = (knowledgeBaseID: number) => {
        const patchItems = this.getState().knowledge.navigation.patchItems;
        const params = {
            transactionID: uniqueId("patchNav"),
            patchItems,
            knowledgeBaseID,
        };
        const apiThunk = bindThunkAction(NavigationActions.patchNavigationFlatACs, async () => {
            const response = await this.api.patch(`/knowledge-bases/${knowledgeBaseID}/navigation-flat`, patchItems);
            return response.data;
        })(params);
        return this.dispatch(apiThunk);
    };
}
export function useNavigationActions() {
    const dispatch = useDispatch();
    const actions = useMemo(() => new NavigationActions(dispatch, apiv2), [dispatch]);
    return actions;
}
