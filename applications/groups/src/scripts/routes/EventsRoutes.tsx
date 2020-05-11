/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import RouteHandler from "@library/routing/RouteHandler";
import { getMeta } from "@library/utility/appUtils";

function getEventPath(path: string = "") {
    const newEventPage = getMeta("themeFeatures.UseNewEventsPage", false);
    const base = newEventPage ? "events" : "new-events";

    return `/${base}/${path}`;
}

export const EventsRoute = new RouteHandler(
    () => import(/* webpackChunkName: "events/pages/EventsPage" */ "@groups/events/pages/EventsPage"),
    getEventPath(),
    (data?: { parentRecordType: string; parentRecordID: number }) => getEventPath(),
);

export function getEventsRoutes() {
    return [EventsRoute.route];
}
