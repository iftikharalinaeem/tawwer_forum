/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { TitleBar } from "@library/headers/TitleBar";
import { CommunityFilterContext } from "@subcommunities/CommunityFilterContext";
import { SubcommunityChooserDropdown } from "@subcommunities/chooser/SubcommunityChooser";
import TitleBarNav from "@library/headers/mebox/pieces/TitleBarNav";

TitleBar.registerBeforeMeBox(() => (
    <CommunityFilterContext.Provider value={{ hideNoProductCommunities: true, linkSuffix: "/kb" }}>
        <SubcommunityChooserDropdown />
    </CommunityFilterContext.Provider>
));

TitleBarNav.addNavItem(() => (
    <CommunityFilterContext.Provider value={{ hideNoProductCommunities: true, linkSuffix: "/kb" }}>
        <SubcommunityChooserDropdown />
    </CommunityFilterContext.Provider>
));
