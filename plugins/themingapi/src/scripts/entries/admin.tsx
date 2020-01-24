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

import React from "react";

registerReducer("themeEditor", themeEditorReducer);
addComponent("theme-editor", ThemeEditorPage);

registerReducer("themeSettings", themeSettingsReducer);
addComponent("theming-ui-manage", ManageThemingPage);

Router.addRoutes([ThemeEditorRoute.route]);
