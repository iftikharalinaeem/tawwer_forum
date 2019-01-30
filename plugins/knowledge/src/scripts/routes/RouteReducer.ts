/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import RouteActions from "@knowledge/routes/RouteActions";
import { reducerWithInitialState } from "typescript-fsa-reducers";

export interface IRouteError {
    message: string;
    status: number;
    description?: string;
}

export interface IRouteState {
    error: IRouteError | null;
}

const INITIAL_STATE: IRouteState = {
    error: null,
};

const routeReducer = reducerWithInitialState<IRouteState>(INITIAL_STATE)
    .case(RouteActions.resetAC, () => INITIAL_STATE)
    .case(RouteActions.errorAC, (state, payload) => {
        return {
            error: payload,
        };
    });
export default routeReducer;
