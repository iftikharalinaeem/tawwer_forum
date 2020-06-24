/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import Tokens, { ITokenProps } from "@vanilla/library/src/scripts/forms/select/Tokens";
import { LoadStatus } from "@vanilla/library/src/scripts/@types/api/core";
import { notEmpty } from "@vanilla/utils";
import { useSubcommunities } from "@subcommunities/subcommunities/subcommunitySelectors";
import Loader from "@library/loaders/Loader";
import { IComboBoxOption } from "@library/features/search/SearchBar";

interface IProps extends Omit<ITokenProps, "options" | "isLoading" | "value" | "onChange" | "label"> {
    value: number[];
    onChange: (value: IComboBoxOption[]) => void;
    label?: string;
}

export function MultiSubcommunityInput(props: IProps) {
    const { subcommunitiesByID } = useSubcommunities();
    const data = subcommunitiesByID.data;

    if (!data) {
        return <Loader small />;
    }

    return (
        <Tokens
            {...props}
            label={props.label ?? ""}
            value={props.value
                .map(id => {
                    const subCom = subcommunitiesByID.data?.[id];
                    if (!subCom) {
                        return null;
                    } else {
                        return {
                            label: subCom.name,
                            value: id,
                        };
                    }
                })
                .filter(notEmpty)}
            onChange={value => {
                props.onChange(value);
            }}
            options={Object.keys(data)
                .map(id => {
                    const subCom = subcommunitiesByID.data?.[id];
                    if (!subCom) {
                        return null;
                    } else {
                        return {
                            label: subCom.name,
                            value: id,
                        };
                    }
                })
                .filter(notEmpty)}
            isLoading={[LoadStatus.PENDING, LoadStatus.LOADING].includes(subcommunitiesByID.status)}
        />
    );
}
