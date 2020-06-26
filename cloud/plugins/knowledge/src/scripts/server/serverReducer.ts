/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { reducerWithInitialState } from "typescript-fsa-reducers";
import produce from "immer";
import ServerActions from "@knowledge/server/ServerActions";

export interface IServerState {
    localDeploymentKey: string | null;
    serverDeploymentKey: string | null;
}

const INITIAL_STATE: IServerState = {
    localDeploymentKey: null,
    serverDeploymentKey: null,
};

export const serverReducer = produce(
    reducerWithInitialState(INITIAL_STATE)
        .case(ServerActions.setLocalDeploymentKey, (state, payload) => {
            state.localDeploymentKey = payload.result;
            return state;
        })
        .case(ServerActions.setServerDeploymentKey, (state, payload) => {
            state.serverDeploymentKey = payload.result;
            return state;
        }),
);
