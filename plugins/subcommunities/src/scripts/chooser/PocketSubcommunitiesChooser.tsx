/*
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useState } from "react";
import { DashboardFormGroup } from "@dashboard/forms/DashboardFormGroup";
import { t } from "@vanilla/i18n/src";
import { MultiSubcommunityInput } from "./MultiSubcommunityInput";
import { IComboBoxOption } from "@library/features/search/SearchBar";

const sanitizeValue = (value: any) => {
    if (Array.isArray(value)) {
        return value;
    } else {
        return !value || value === "" ? [] : value;
    }
};

export function PocketSubcommunityChooser(props) {
    const [subcommunityIDs, setSubcommunityIDs] = useState(sanitizeValue(props.value));

    return (
        <DashboardFormGroup label={t("Subcommunities")} tag={"div"}>
            <div className="input-wrap">
                <MultiSubcommunityInput
                    value={subcommunityIDs}
                    onChange={selectedSubCommunities => {
                        setSubcommunityIDs(
                            selectedSubCommunities.map(subCom => {
                                return parseInt(subCom.value.toString());
                            }),
                        );
                    }}
                />
            </div>
            {!subcommunityIDs ||
                (subcommunityIDs.length === 0 && <input name={props.fieldName + []} type={"hidden"} value={[]} />)}
            {subcommunityIDs.map((subComID, key) => {
                return <input key={key} name={props.fieldName + "[]"} type={"hidden"} value={subComID} />;
            })}
        </DashboardFormGroup>
    );
}
