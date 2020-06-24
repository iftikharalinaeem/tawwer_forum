/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { useContext } from "react";
import { getMeta } from "@vanilla/library/src/scripts/utility/appUtils";

interface IProps {
    hideNoProductCommunities: boolean; // Enabling this will filter any community not attached to a product.
}

export const CommunityFilterContext = React.createContext<IProps>({
    hideNoProductCommunities: getMeta("featureFlags.SubcommunityProducts.Enabled"),
});

export function useCommunityFilterContext() {
    return useContext(CommunityFilterContext);
}
