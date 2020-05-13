/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import RouteHandler from "@library/routing/RouteHandler";
import { getMeta } from "@library/utility/appUtils";
import { EventsPagePlaceholder } from "@groups/events/pages/EventsPagePlaceholder";

function getEventPath(path: string = "") {
    const newEventPage = getMeta("themeFeatures.NewEventsPage", false);
    const base = newEventPage ? "events" : "new-events";

    return `/${base}${path}`;
}

export const EventsRoute = new RouteHandler(
    () => import(/* webpackChunkName: "events/pages/EventsPage" */ "@groups/events/pages/EventsPage"),
    [getEventPath("/:parentRecordType/:parentRecordID(-?\\d+)(-[^/]+)?"), getEventPath()],
    (data?: { parentRecordType: string; parentRecordID: number }) =>
        getEventPath(`/${data?.parentRecordType}/${data?.parentRecordID}`),
    EventsPagePlaceholder,
);

export const EventRoute = new RouteHandler(
    () => import(/* webpackChunkName: "events/pages/EventPage" */ "@groups/events/pages/EventPage"),
    getEventPath("/:id"),
    (data: { id: number }) => getEventPath(`/${data.id}`),
);

export function getEventsRoutes() {
    return [EventsRoute.route, EventRoute.route];
}
