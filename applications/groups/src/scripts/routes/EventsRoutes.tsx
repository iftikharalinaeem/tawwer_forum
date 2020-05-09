/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import RouteHandler from "@library/routing/RouteHandler";

export const EventsRoute = new RouteHandler(
    () => import(/* webpackChunkName: "events/pages/EventsPage" */ "@groups/events/pages/EventsPage"),
    "/new-events",
    (data?: { parentRecordType: string; parentRecordID: number }) => `/new-events`,
);

export function getEventsRoutes() {
    return [EventsRoute.route];
}
