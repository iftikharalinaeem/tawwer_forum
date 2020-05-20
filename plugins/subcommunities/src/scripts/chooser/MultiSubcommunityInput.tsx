/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import Tokens, { ITokenProps } from "@vanilla/library/src/scripts/forms/select/Tokens";
import { useRoles, useRoleSelectOptions } from "@dashboard/roles/roleHooks";
import { LoadStatus } from "@vanilla/library/src/scripts/@types/api/core";
import { notEmpty } from "@vanilla/utils";
import { useSubcommunities } from "@subcommunities/subcommunities/subcommunitySelectors";
import Loader from "@library/loaders/Loader";

interface IProps extends Omit<ITokenProps, "options" | "isLoading" | "value" | "onChange"> {
    value: number[];
    onChange: (tokens: number[]) => void;
}

export function MultiSubcommunityInput(props: IProps) {
    const { subcommunitiesByID } = useSubcommunities();
    const communityData = subcommunitiesByID.data;

    console.log("subcommunitiesByID: ", subcommunitiesByID);

    if (!communityData) {
        return <Loader small />;
    }

    return (
        <Tokens
            {...props}
            value={props.value
                .map(subcommunityID => {
                    const subcommunity = subcommunitiesByID?.[subcommunityID];
                    if (!subcommunity) {
                        return null;
                    } else {
                        return {
                            label: subcommunity.name,
                            value: subcommunityID,
                        };
                    }
                })
                .filter(notEmpty)}
            onChange={options => {
                const result = options?.map(option => option.value as number);
                props.onChange(result);
            }}
            options={subcommunitiesByID ?? []}
            isLoading={[LoadStatus.PENDING, LoadStatus.LOADING].includes(subcommunitiesByID.status)}
        />
    );
}
