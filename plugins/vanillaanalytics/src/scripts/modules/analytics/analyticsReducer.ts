/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { reducerWithInitialState } from "typescript-fsa-reducers";
import { produce } from "immer";
import AnalyticsActions from "./AnalyticsActions";

export interface IAnalyticsConfig {
    projectID?: string | null;
    writeKey?: string | null;
}

export interface IAnalyticsEventDefaults {
    dateTime?: {
        year: number;
        month: number;
        day: number;
        hour: number;
        minute: number;
        dayOfWeek: number;
        startOfWeek: number;
        timestamp: number;
        timeZone: string | null;
    };
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
