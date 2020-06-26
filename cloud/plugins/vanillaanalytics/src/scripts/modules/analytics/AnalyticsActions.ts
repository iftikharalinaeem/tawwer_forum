/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import ReduxActions from "@library/redux/ReduxActions";
import actionCreatorFactory from "typescript-fsa";
import { IAnalyticsConfig, IAnalyticsEventDefaults } from "./analyticsReducer";

interface IGetConfig {
    result: IAnalyticsConfig;
}

interface IGetEventDefaults {
    result: IAnalyticsEventDefaults;
}

const createAction = actionCreatorFactory("@@analytics");

export default class AnalyticsActions extends ReduxActions {
    public static getConfig = createAction<IGetConfig>("GET_CONFIG");
    public getConfig = this.bindDispatch(AnalyticsActions.getConfig);

    public static getEventDefaults = createAction<IGetEventDefaults>("GET_EVENT_DEFAULTS");
    public getEventDefaults = this.bindDispatch(AnalyticsActions.getEventDefaults);
}
