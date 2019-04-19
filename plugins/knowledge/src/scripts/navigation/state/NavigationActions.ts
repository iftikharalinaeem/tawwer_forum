/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { IKbNavigationItem, IPatchFlatItem } from "@knowledge/navigation/state/NavigationModel";
import { IStoreState } from "@knowledge/state/model";
import { IApiError, LoadStatus } from "@library/@types/api/core";
import ReduxActions, { bindThunkAction } from "@library/redux/ReduxActions";
import uniqueId from "lodash/uniqueId";
import { actionCreatorFactory } from "typescript-fsa";

const createAction = actionCreatorFactory("@@navigation");

/**
 * Redux actions for knowledge base navigation data.
 */
export default class NavigationActions extends ReduxActions<IStoreState> {
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
        if (!forceUpdate && fetchStatus === LoadStatus.PENDING) {
            return;
        }

        const apiThunk = bindThunkAction(NavigationActions.getNavigationFlatACs, async () => {
            const response = await this.api.get(`/knowledge-bases/${knowledgeBaseID}/navigation-flat`);
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
