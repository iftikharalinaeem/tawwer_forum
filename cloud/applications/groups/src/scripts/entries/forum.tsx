/*
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { registerReducer } from "@library/redux/reducerRegistry";
import { eventsReducer } from "@groups/events/state/EventsReducer";
import { Router } from "@library/Router";
import { getEventsRoutes } from "@groups/routes/EventsRoutes";

import { addComponent, addPageComponent } from "@library/utility/componentRegistry";
import { EventsPanel } from "@groups/events/ui/EventsPanel";
registerReducer("events", eventsReducer);

addComponent("events-page", () => <Router disableDynamicRouting />);
addComponent("new-events-module", EventsPanel);

addPageComponent(() => <Router disableDynamicRouting />);

Router.addRoutes(getEventsRoutes());