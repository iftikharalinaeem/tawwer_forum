/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { onPageView } from "@library/pageViews/pageViewTracking";
import getStore from "@library/redux/getStore";
import { registerReducer } from "@library/redux/reducerRegistry";
import { logError } from "@library/utility/utils";
import KeenTracking from "keen-tracking";
import { analyticsReducer, IStoreState } from "../modules/analytics/analyticsReducer";

registerReducer("analytics", analyticsReducer);

onPageView(() => {
    const { config, eventDefaults } = getStore<IStoreState>().getState().analytics;

    // If we don't have a project or a write key, we can't do anything.
    if (!config.projectID || !config.writeKey) {
        return;
    }

    const client = new KeenTracking({
        projectId: config.projectID,
        writeKey: config.writeKey,
    });

    client.recordEvent("page", eventDefaults).catch(error => {
        logError(error);
    });
});
