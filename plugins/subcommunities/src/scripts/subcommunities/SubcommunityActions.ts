/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import ReduxActions, { bindThunkAction } from "@library/redux/ReduxActions";
import { actionCreatorFactory } from "typescript-fsa";
import { ISubcommunity } from "@subcommunities/subcommunities/subcommunityTypes";
import { IApiError, LoadStatus } from "@library/@types/api/core";
import { useDispatch } from "react-redux";
import { useMemo } from "react";
import apiv2 from "@library/apiv2";
import { IMultiSiteStoreState } from "@subcommunities/state/model";

const actionCreator = actionCreatorFactory("@@subcommunities");

type GetAllRequest = {};
type GetAllResponse = ISubcommunity[];

export class SubcommunityActions extends ReduxActions<IMultiSiteStoreState> {
    public static readonly getAllACs = actionCreator.async<GetAllRequest, GetAllResponse, IApiError>("GET_ALL");

    public getAll = (force: boolean = false) => {
        if (!force && this.getState().multisite.subcommunities.subcommunitiesByID.status !== LoadStatus.PENDING) {
            // Only make the request if we haven't started it yet.
            return;
        }
        const apiThunk = bindThunkAction(SubcommunityActions.getAllACs, async () => {
            const response = await this.api.get("/subcommunities?expand=all");
            return response.data;
        })();

        return this.dispatch(apiThunk);
    };
}

export function useSubcommunityActions() {
    const dispatch = useDispatch();
    const actions = useMemo(() => {
        return new SubcommunityActions(dispatch, apiv2);
    }, [dispatch]);
    return actions;
}
