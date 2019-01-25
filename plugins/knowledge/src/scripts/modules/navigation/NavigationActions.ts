/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import ReduxActions, { bindThunkAction } from "@library/state/ReduxActions";
import { IStoreState } from "@knowledge/state/model";
import { LoadStatus, IApiError } from "@library/@types/api";
import uniqueId from "lodash/uniqueId";
import { actionCreatorFactory } from "typescript-fsa";
import { IKbNavigationItem, IPatchFlatItem } from "@knowledge/modules/navigation/NavigationModel";

const createAction = actionCreatorFactory("@@navigation");

/**
 * Redux actions for knowledge base navigation data.
 */
export default class NavigationActions extends ReduxActions {
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
    public getNavigationFlat = (knowledgeBaseID: number, forceUpdate = false) => {
        const state = this.getState<IStoreState>();
        const fetchLoadable = state.knowledge.navigation.fetchLoadablesByKbID[knowledgeBaseID];
        if (!forceUpdate && fetchLoadable && fetchLoadable.status === LoadStatus.SUCCESS) {
            return;
        }

        const apiThunk = bindThunkAction(NavigationActions.getNavigationFlatACs, async () => {
            const response = await this.api.get(`/knowledge-navigation/flat?knowledgeBaseID=${knowledgeBaseID}`);
            return response.data;
        })({ knowledgeBaseID });

        return this.dispatch(apiThunk);
    };

    public static patchNavigationFlatACs = createAction.async<
        { knowledgeBaseID: number; patchItems: IPatchFlatItem[]; transactionID: string },
        IKbNavigationItem[],
        IApiError
    >("PATCH_NAVIGATION_FLAT");

    /**
     * Patch a knowlege base's navigation using the flat format.
     *
     * @param patchItems Patch request parameters.
     */
    public patchNavigationFlat = (knowledgeBaseID: number, patchItems: IPatchFlatItem[]) => {
        const params = {
            transactionID: uniqueId("patchNav"),
            patchItems,
            knowledgeBaseID,
        };
        const apiThunk = bindThunkAction(NavigationActions.patchNavigationFlatACs, async () => {
            const response = await this.api.patch(`/knowledge-navigation/${knowledgeBaseID}/flat`, patchItems);
            return response.data;
        })(params);
        return this.dispatch(apiThunk);
    };
}
