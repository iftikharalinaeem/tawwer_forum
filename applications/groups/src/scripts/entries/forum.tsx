/*
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { registerReducer } from "@library/redux/reducerRegistry";
import { eventsReducer } from "@groups/events/state/EventsReducer";
import { Router } from "@library/Router";
import { getEventsRoutes } from "@groups/routes/EventsRoutes";
import { addComponent } from "@library/utility/componentRegistry";
import EventsPage from "@groups/events/pages/EventsPage";
// import "@dashboard/legacy";

registerReducer("Events", eventsReducer);
addComponent("events-page", EventsPage);
Router.addRoutes(getEventsRoutes());
