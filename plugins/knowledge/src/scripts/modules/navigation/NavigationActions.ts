/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import ReduxActions, { ActionsUnion } from "@library/state/ReduxActions";
import {
    IGetKbNavigationRequest,
    IGetKbNavigationResponse,
    IPatchKBNavigationRequest,
    IPatchKbNavigationResponse,
} from "@knowledge/@types/api";
import { IStoreState } from "@knowledge/state/model";
import { LoadStatus } from "@library/@types/api";
import uniqueId from "lodash/uniqueId";

/**
 * Redux actions for knowledge base navigation data.
 */
export default class NavigationActions extends ReduxActions {
    /**
     * Union of all possible action types in this class.
     */
    public static readonly ACTION_TYPES:
        | ActionsUnion<typeof NavigationActions.getNavigationFlatACs>
        | ActionsUnion<typeof NavigationActions.patchNavigationFlatACs>;

    /**
     * GET /knowledge-navigation/flat
     */

    public static readonly GET_NAVIGATION_FLAT_REQUEST = "@@navigation/GET_NAVIGATION_FLAT_REQUEST";
    public static readonly GET_NAVIGATION_FLAT_RESPONSE = "@@navigation/GET_NAVIGATION_FLAT_RESPONSE";
    public static readonly GET_NAVIGATION_FLAT_ERROR = "@@navigation/GET_NAVIGATION_FLAT_ERROR";

    /**
     * Action creators for getting a knowledge base's navigation in the flat format.
     */
    private static getNavigationFlatACs = ReduxActions.generateApiActionCreators(
        NavigationActions.GET_NAVIGATION_FLAT_REQUEST,
        NavigationActions.GET_NAVIGATION_FLAT_RESPONSE,
        NavigationActions.GET_NAVIGATION_FLAT_ERROR,
        {} as IGetKbNavigationResponse,
        {} as IGetKbNavigationRequest,
    );

    /**
     * Get navigation for a knowledge base in the flat format.
     *
     * @param request Parameters for the request.
     */
    public getNavigationFlat = (request: IGetKbNavigationRequest, forceUpdate = false) => {
        const state = this.getState<IStoreState>();
        const { fetchLoadable } = state.knowledge.navigation;
        if (!forceUpdate && fetchLoadable.status === LoadStatus.SUCCESS) {
            return;
        }

        return this.dispatchApi<IGetKbNavigationResponse>(
            "get",
            `/knowledge-navigation/flat`,
            NavigationActions.getNavigationFlatACs,
            request,
        );
    };

    /**
     * GET /knowledge-navigation/flat
     */

    public static readonly PATCH_NAVIGATION_FLAT_REQUEST = "@@navigation/PATCH_NAVIGATION_FLAT_REQUEST";
    public static readonly PATCH_NAVIGATION_FLAT_RESPONSE = "@@navigation/PATCH_NAVIGATION_FLAT_RESPONSE";
    public static readonly PATCH_NAVIGATION_FLAT_ERROR = "@@navigation/PATCH_NAVIGATION_FLAT_ERROR";

    /**
     * Action creators for patching a knowledge base's navigation using the flat format.
     */
    private static patchNavigationFlatACs = ReduxActions.generateApiActionCreators(
        NavigationActions.PATCH_NAVIGATION_FLAT_REQUEST,
        NavigationActions.PATCH_NAVIGATION_FLAT_RESPONSE,
        NavigationActions.PATCH_NAVIGATION_FLAT_ERROR,
        {} as IPatchKbNavigationResponse,
        {} as IPatchKBNavigationRequest & { transactionID: string },
    );

    /**
     * Patch a knowlege base's navigation using the flat format.
     *
     * @param request Patch request parameters.
     */
    public patchNavigationFlat = (request: IPatchKBNavigationRequest) => {
        return this.dispatchApi<IPatchKbNavigationResponse>(
            "patch",
            `/knowledge-navigation/flat`,
            NavigationActions.patchNavigationFlatACs,
            request,
            { transactionID: uniqueId("patchNav") },
        );
    };
}
