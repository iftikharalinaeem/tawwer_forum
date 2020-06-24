/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { produce } from "immer";
import { reducerWithInitialState } from "typescript-fsa-reducers";
import AnalyticsActions from "./AnalyticsActions";
import { IExpandedDateTime } from "./tracking";

export interface IAnalyticsConfig {
    projectID?: string | null;
    writeKey?: string | null;
}

export interface IAnalyticsEventDefaults {
    dateTime?: IExpandedDateTime;
    ip?: string;
    site?: {
        accountID: number | null;
        name: string | null;
        siteID: number | null;
    };
    url?: string;
    _country?: string | null;
    user?: {
        dateFirstVisit: string | null;
        name: string;
        roleType: string;
        userID: number;
    };
    userAgent?: string;
    keen?: {
        addons: any[];
    };
}

interface IAnalyticsState {
    config: IAnalyticsConfig;
    eventDefaults: IAnalyticsEventDefaults;
}

export interface IStoreState {
    analytics: IAnalyticsState;
}

const INITIAL_STATE: IAnalyticsState = {
    config: {},
    eventDefaults: {},
};

export const analyticsReducer = produce(
    reducerWithInitialState(INITIAL_STATE)
        .case(AnalyticsActions.getConfig, (state, payload) => {
            state.config = payload.result;
            return state;
        })
        .case(AnalyticsActions.getEventDefaults, (state, payload) => {
            state.eventDefaults = payload.result;
            return state;
        }),
);
