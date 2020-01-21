/*
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import ManageThemingPage from "@themingapi/theming-ui-settings/ManageThemingPage";
import { addComponent } from "@library/utility/componentRegistry";
import { registerReducer } from "@library/redux/reducerRegistry";
import { themeEditorReducer } from "@themingapi/theme/themeEditorReducer";
import { themeSettingsReducer } from "@library/theming/themeSettingsReducer";
import ThemeEditorPage from "@themingapi/theme/ThemeEditorPage";

import { Router } from "@library/Router";
import { ThemeEditorRoute } from "@themingapi/routes/themeEditorRoutes";
import { AppContext } from "@library/AppContext";
import ErrorPage from "@knowledge/pages/ErrorPage";
import React from "react";
import { mountReact } from "@vanilla/react-utils/src";
import PageLoader from "@library/routing/PageLoader";
import { onReady } from "@library/utility/appUtils";
import { LoadStatus } from "@library/@types/api/core";

registerReducer("themeEditor", themeEditorReducer);
addComponent("theme-editor", ThemeEditorPage);

registerReducer("themeSettings", themeSettingsReducer);
addComponent("theming-ui-manage", ManageThemingPage);

Router.addRoutes([ThemeEditorRoute.route]);
