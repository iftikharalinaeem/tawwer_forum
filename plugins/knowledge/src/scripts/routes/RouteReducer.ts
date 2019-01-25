/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import ReduxReducer from "@library/state/ReduxReducer";
import { Reducer } from "redux";
import RouteActions from "@knowledge/routes/RouteActions";
import produce from "immer";

export interface IRouteError {
    message: string;
    status: number;
    description?: string;
}

export interface IRouteState {
    error: IRouteError | null;
}

type ReducerType = Reducer<IRouteState, typeof RouteActions.ACTION_TYPES>;

export default class RouteReducer implements ReduxReducer<IRouteState> {
    public readonly initialState: IRouteState = {
        error: null,
    };

    public reducer: ReducerType = (state = this.initialState, action) => {
        return produce(state, nextState => {
            if (action.type === RouteActions.ERROR) {
                nextState.error = action.payload;
            }
        });
    };
}
