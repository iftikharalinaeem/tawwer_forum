/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import ReduxActions from "@library/state/ReduxActions";
import { IRouteError } from "@knowledge/routes/RouteReducer";
import actionCreatorFactory from "typescript-fsa";

const createAction = actionCreatorFactory("@@navigation");

export default class RouteActions {
    public static errorAC = createAction<IRouteError>("ERROR");
    public static resetAC = createAction("RESET");
}
