/*
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { registerReducer } from "@library/redux/reducerRegistry";
import { eventsReducer } from "@groups/events/state/EventsReducer";
import { Router } from "@library/Router";
import { getEventsRoutes } from "@groups/routes/EventsRoutes";
import { eventReducer } from "@groups/events/state/EventReducer";

import { addComponent, addPageComponent } from "@library/utility/componentRegistry";
registerReducer("events", eventsReducer);

addComponent("events-page", () => <Router disableDynamicRouting />);
addPageComponent(() => <Router disableDynamicRouting />);

registerReducer("event", eventReducer);

Router.addRoutes(getEventsRoutes());
