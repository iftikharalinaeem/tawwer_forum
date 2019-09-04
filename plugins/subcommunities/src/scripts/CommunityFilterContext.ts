/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { useContext } from "react";

interface IProps {
    hideNoProductCommunities?: boolean;
    linkSuffix?: string; // Some suffix to put on the URL path when switching communities.
}

export const CommunityFilterContext = React.createContext<IProps>({});
export function useCommunityFilterContext() {
    return useContext(CommunityFilterContext);
}
