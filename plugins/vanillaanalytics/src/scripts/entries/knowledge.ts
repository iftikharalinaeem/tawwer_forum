/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { onPageView } from "@library/pageViews/pageViewTracking";
import { registerReducer } from "@library/redux/reducerRegistry";
import { analyticsReducer } from "../modules/analytics/analyticsReducer";
import { trackPageView } from "../modules/analytics/tracking";

registerReducer("analytics", analyticsReducer);

onPageView(trackPageView);
