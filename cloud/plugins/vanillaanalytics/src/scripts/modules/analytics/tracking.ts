/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import getStore from "@library/redux/getStore";
import { logError } from "@vanilla/utils";
import KeenTracking from "keen-tracking";
import { IStoreState } from "./analyticsReducer";
import clone from "lodash/clone";

enum Collection {
    ARTICLE = "article",
    ARTICLE_MODIFY = "article_modify",
    ARTICLE_REACTION = "article_reaction",
    ERROR = "error",
    PAGE = "page",
    POST = "post",
    SESSION = "session",
}

export interface IExpandedDateTime {
    year: number;
    month: number;
    day: number;
    hour: number;
    minute: number;
    dayOfWeek: number;
    startOfWeek: number | null;
    timestamp: number;
    timeZone: string | null;
}

const dateTime = (): IExpandedDateTime => {
    const date = new Date();
    return {
        year: date.getFullYear(),
        month: date.getMonth(),
        day: date.getDate(),
        hour: date.getHours(),
        minute: date.getMinutes(),
        dayOfWeek: date.getDay(),
        startOfWeek: null,
        timestamp: Math.floor(date.getTime() / 1000),
        timeZone: null,
    };
};

const eventDefaults = () => {
    const data = clone(getStore<IStoreState>().getState().analytics.eventDefaults);
    data.dateTime = dateTime();
    return data;
};

export const trackPageView = (url: string, context?: object) => {
    let data = eventDefaults();
    data.url = url;

    if (context) {
        data = { ...data, ...context };
    }

    trackEvent(Collection.PAGE, data);
};

export const trackEvent = (collection: Collection, data) => {
    const { config } = getStore<IStoreState>().getState().analytics;

    // If we don't have a project or a write key, we can't do anything.
    if (!config.projectID || !config.writeKey) {
        return;
    }

    const client = new KeenTracking({
        projectId: config.projectID,
        writeKey: config.writeKey,
    });

    client.recordEvent(collection, data).catch(error => {
        logError(error);
    });
};
