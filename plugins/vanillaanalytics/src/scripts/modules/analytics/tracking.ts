/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import getStore from "@library/redux/getStore";
import { logError } from "@library/utility/utils";
import KeenTracking from "keen-tracking";
import { IStoreState } from "./analyticsReducer";

enum Collection {
    ARTICLE = "article",
    ARTICLE_MODIFY = "article_modify",
    ARTICLE_REACTION = "article_reaction",
    ERROR = "error",
    PAGE = "page",
    POST = "post",
    SESSION = "session",
}

export const trackPageView = () => {
    const { eventDefaults } = getStore<IStoreState>().getState().analytics;
    trackEvent(Collection.PAGE, eventDefaults);
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
