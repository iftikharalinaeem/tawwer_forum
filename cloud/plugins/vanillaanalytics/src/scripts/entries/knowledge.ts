/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { onPageView } from "@library/pageViews/pageViewTracking";
import { registerReducer } from "@library/redux/reducerRegistry";
import { analyticsReducer } from "../modules/analytics/analyticsReducer";
import { trackPageView } from "../modules/analytics/tracking";
import { History } from "history";
import { onPageViewWithContext } from "@library/analytics/AnalyticsData";

registerReducer("analytics", analyticsReducer);

onPageView((params: { history: History }) => {
    trackPageView(window.location.href);
});

onPageViewWithContext((event: CustomEvent) => {
    trackPageView(window.location.href, event.detail);
});
