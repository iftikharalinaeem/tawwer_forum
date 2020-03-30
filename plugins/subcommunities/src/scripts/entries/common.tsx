/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import getStore from "@library/redux/getStore";
import { registerReducer } from "@library/redux/reducerRegistry";
import { getMeta, onReady } from "@library/utility/appUtils";
import {
    SubcommunityChooserDropdown,
    SubcommunityChooserHamburgerGroup,
} from "@subcommunities/chooser/SubcommunityChooser";
import { ProductActions } from "@subcommunities/products/ProductActions";
import multiSiteReducer from "@subcommunities/state/reducer";
import { addHamburgerNavGroup } from "@vanilla/library/src/scripts/flyouts/Hamburger";
import { ButtonTypes } from "@library/forms/buttonTypes";
import TitleBar from "@vanilla/library/src/scripts/headers/TitleBar";
import { Devices, useDevice } from "@vanilla/library/src/scripts/layout/DeviceContext";
import { addComponent } from "@vanilla/library/src/scripts/utility/componentRegistry";
import React from "react";

registerReducer("multisite", multiSiteReducer);

onReady(() => {
    const store = getStore();
    store.dispatch(
        ProductActions.putFeatureFlagACs.done({
            params: { enabled: false },
            result: { enabled: getMeta("featureFlags.SubcommunityProducts.Enabled", false) },
        }),
    );
});
addComponent("subcommunity-chooser", SubcommunityChooserDropdown);

TitleBar.registerBeforeMeBox(() => {
    const device = useDevice();
    if (device === Devices.MOBILE || device === Devices.XS) {
        return null;
    }
    return <SubcommunityChooserDropdown buttonType={ButtonTypes.TITLEBAR_LINK} />;
});

addHamburgerNavGroup(SubcommunityChooserHamburgerGroup);
