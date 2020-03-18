/*
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import ManageThemingPage from "@themingapi/theming-ui-settings/ManageThemingPage";
import { addComponent } from "@library/utility/componentRegistry";
import { registerReducer } from "@library/redux/reducerRegistry";
import { themeEditorReducer } from "@themingapi/theme/themeEditorReducer";
import { themeSettingsReducer } from "@library/theming/themeSettingsReducer";
import { Router } from "@library/Router";
import { getThemeRoutes } from "@themingapi/routes/themeEditorRoutes";

registerReducer("themeEditor", themeEditorReducer);

registerReducer("themeSettings", themeSettingsReducer);
addComponent("theming-ui-manage", ManageThemingPage);

Router.addRoutes(getThemeRoutes());

// Hide the old theme page.
document.querySelector(".nav-link-appearance-themes")?.remove();
