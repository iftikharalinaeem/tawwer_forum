/**
 * UI testing pages
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import React from "react";
import { Route } from "react-router-dom";
import { addRoutes } from "@dashboard/application";
import UITestAuthenticationPage from "./pages/UITestAuthenticationPage";

addRoutes([<Route exact path="/uitests/authentication" component={UITestAuthenticationPage} />]);
