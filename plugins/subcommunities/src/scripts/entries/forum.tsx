/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { addComponent } from "@library/utility/componentRegistry";
import { SubcommunityChooserDropdown } from "@subcommunities/chooser/SubcommunityChooser";
import { getMeta } from "@library/utility/appUtils";
import { CommunityFilterContext } from "@subcommunities/CommunityFilterContext";

const providerArgs = {
    hideNoProductCommunities: getMeta("featureFlags.SubcommunityProducts.Enabled"),
    linkSuffix: "",
};

const ChooserWithProvider = () => (
    <CommunityFilterContext.Provider value={providerArgs}>
        <SubcommunityChooserDropdown />
    </CommunityFilterContext.Provider>
);

addComponent("subcommunity-chooser", ChooserWithProvider);
