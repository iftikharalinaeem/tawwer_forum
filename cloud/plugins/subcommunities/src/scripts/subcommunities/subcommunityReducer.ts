/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { ILoadable, LoadStatus } from "@library/@types/api/core";
import { produce } from "immer";
import { reducerWithInitialState } from "typescript-fsa-reducers";
import { ISubcommunity } from "@subcommunities/subcommunities/subcommunityTypes";
import { SubcommunityActions } from "@subcommunities/subcommunities/SubcommunityActions";

export interface ISubcommunitiesState {
    subcommunitiesByID: ILoadable<{ [id: number]: ISubcommunity }>;
}

const INITIAL_SUBCOMMUNITIES_STATE: ISubcommunitiesState = {
    subcommunitiesByID: {
        status: LoadStatus.PENDING,
    },
};

export const subcommunityReducer = produce(
    reducerWithInitialState(INITIAL_SUBCOMMUNITIES_STATE)
        .case(SubcommunityActions.getAllACs.started, state => {
            state.subcommunitiesByID.status = LoadStatus.LOADING;
            return state;
        })
        .case(SubcommunityActions.getAllACs.done, (state, payload) => {
            state.subcommunitiesByID.status = LoadStatus.SUCCESS;
            const subcommunities = {};
            payload.result.forEach(subcommunity => {
                subcommunities[subcommunity.subcommunityID] = subcommunity;
            });
            state.subcommunitiesByID.data = subcommunities;
            return state;
        })
        .case(SubcommunityActions.getAllACs.failed, (state, payload) => {
            state.subcommunitiesByID.status = LoadStatus.ERROR;
            state.subcommunitiesByID.error = payload.error;
            return state;
        }),
);
