/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */
import React from "react";
import { useSubcommunities } from "@subcommunities/subcommunities/subcommunitySelectors";
import Loader from "@library/loaders/Loader";

interface IProps {
    subcommunityIDs: number[];
}

export function SubcommunityList(props: IProps) {
    const { subcommunitiesByID } = useSubcommunities();
    const communityData = subcommunitiesByID.data;

    if (!communityData) {
        return <Loader small />;
    }

    return (
        <ul>
            {props.subcommunityIDs.map(id => {
                return <li key={id}>{communityData[id].name + ` (${communityData[id].locale})`}</li>;
            })}
        </ul>
    );
}
