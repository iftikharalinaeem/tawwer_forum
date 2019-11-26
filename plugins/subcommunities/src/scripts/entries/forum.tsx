/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { addComponent } from "@library/utility/componentRegistry";
import { SubcommunityChooserDropdown } from "@subcommunities/chooser/SubcommunityChooser";
import { getMeta } from "@library/utility/appUtils";
import { CommunityFilterContext } from "@subcommunities/CommunityFilterContext";
import { TitleBar } from "@library/headers/TitleBar";
import { useDevice, Devices } from "@library/layout/DeviceContext";
import { ButtonTypes } from "@library/forms/buttonStyles";
import TitleBarNav from "@library/headers/mebox/pieces/TitleBarNav";

const providerArgs = {
    hideNoProductCommunities: getMeta("featureFlags.SubcommunityProducts.Enabled"),
    linkSuffix: "",
};

const ChooserWithProvider = props => (
    <CommunityFilterContext.Provider value={providerArgs}>
        <SubcommunityChooserDropdown {...props} />
    </CommunityFilterContext.Provider>
);

addComponent("subcommunity-chooser", ChooserWithProvider);

// New MeBox registration

TitleBar.registerBeforeMeBox(() => {
    const device = useDevice();
    if (device === Devices.MOBILE || device === Devices.XS) {
        return null;
    }
    return (
        <CommunityFilterContext.Provider value={providerArgs}>
            <SubcommunityChooserDropdown buttonType={ButtonTypes.TITLEBAR_LINK} />
        </CommunityFilterContext.Provider>
    );
});
