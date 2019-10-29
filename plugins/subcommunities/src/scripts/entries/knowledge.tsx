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
import { SearchFormFilters } from "@knowledge/modules/search/SearchFormFilters";
import { ProductSearchFormFilter } from "@subcommunities/forms/ProductSeachFormFilter";
import { SearchDomain } from "@knowledge/modules/search/searchPageReducer";

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

TitleBarNav.addNavItem(() => {
    const device = useDevice();

    if (device !== Devices.MOBILE && device !== Devices.XS) {
        return null;
    }

    return (
        <CommunityFilterContext.Provider value={providerArgs}>
            <SubcommunityChooserDropdown buttonType={ButtonTypes.TITLEBAR_LINK} />
        </CommunityFilterContext.Provider>
    );
});

SearchFormFilters.addSearchFilter(SearchDomain.ARTICLES, <ProductSearchFormFilter default="current" />);
SearchFormFilters.addSearchFilter(SearchDomain.EVERYWHERE, <ProductSearchFormFilter default="all" />);
