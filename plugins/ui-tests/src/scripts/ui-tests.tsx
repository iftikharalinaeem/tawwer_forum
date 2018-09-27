/**
 * UI testing pages
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import React from "react";
import { Route } from "react-router-dom";

import UITestAuthenticationPage from "./pages/UITestAuthenticationPage";
import { addRoutes } from "@library/application";

addRoutes([<Route exact path="/uitests/authentication" component={UITestAuthenticationPage} />]);
