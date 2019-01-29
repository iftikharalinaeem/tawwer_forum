/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import ReduxActions from "@library/state/ReduxActions";
import { IRouteError } from "@knowledge/routes/RouteReducer";

export default class RouteActions extends ReduxActions {
    public static readonly ERROR = "@@kbPage/ERROR";

    public static readonly ACTION_TYPES: ReturnType<typeof RouteActions.errorAC>;

    public static errorAC(pageError: IRouteError) {
        return RouteActions.createAction(RouteActions.ERROR, pageError);
    }
}
