/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { ISubcommunity } from "@subcommunities/subcommunities/subcommunityTypes";
import DropDownItemLink from "@vanilla/library/src/scripts/flyouts/items/DropDownItemLink";
import { useCurrentSubcommunity } from "@subcommunities/subcommunities/subcommunitySelectors";

interface IProps {
    subcommunities: ISubcommunity[];
}

export function SingleDepthChooser(props: IProps) {
    const currentSubcommunity = useCurrentSubcommunity();
    return (
        <>
            {props.subcommunities.map(subcommunity => {
                return (
                    <DropDownItemLink
                        key={subcommunity.subcommunityID}
                        to={subcommunity.url}
                        isActive={subcommunity.subcommunityID === currentSubcommunity?.subcommunityID}
                    >
                        {subcommunity.name}
                    </DropDownItemLink>
                );
            })}
        </>
    );
}
