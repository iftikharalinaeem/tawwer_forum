/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { TitleBar } from "@library/headers/TitleBar";
import { CommunityFilterContext } from "@subcommunities/CommunityFilterContext";
import { SubcommunityChooserDropdown } from "@subcommunities/chooser/SubcommunityChooser";
import TitleBarNav from "@library/headers/mebox/pieces/TitleBarNav";
import { useDevice, Devices } from "@library/layout/DeviceContext";
import { ButtonTypes } from "@library/forms/buttonStyles";
import { getMeta } from "@library/utility/appUtils";
import { ProductSearchFormFilter } from "@subcommunities/forms/ProductSeachFormFilter";
import { SearchFilterContextProvider } from "@library/contexts/SearchFilterContext";
import { addHamburgerNavGroup } from "@vanilla/library/src/scripts/flyouts/Hamburger";
import DropDownItemSeparator from "@vanilla/library/src/scripts/flyouts/items/DropDownItemSeparator";
import { dropDownClasses } from "@vanilla/library/src/scripts/flyouts/dropDownStyles";

const providerArgs = {
    hideNoProductCommunities: getMeta("featureFlags.SubcommunityProducts.Enabled"),
    linkSuffix: "/kb",
};

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

addHamburgerNavGroup(() => {
    return (
        <>
            <DropDownItemSeparator />
            <SubcommunityChooserDropdown
                buttonType={ButtonTypes.CUSTOM}
                buttonClass={dropDownClasses().action}
                fullWidth
            />
        </>
    );
});

SearchFilterContextProvider.addSearchFilter(
    "articles",
    <ProductSearchFormFilter searchDomain="articles" default="current" />,
);
SearchFilterContextProvider.addSearchFilter(
    "everywhere",
    <ProductSearchFormFilter searchDomain="everywhere" default="all" />,
);
