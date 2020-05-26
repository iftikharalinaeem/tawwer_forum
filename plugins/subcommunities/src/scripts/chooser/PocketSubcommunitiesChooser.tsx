/*
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useState } from "react";
import { DashboardFormGroup } from "@dashboard/forms/DashboardFormGroup";
import { t } from "@vanilla/i18n/src";
import { componentExists, getComponent } from "@library/utility/componentRegistry";

const sanitizeValue = (value: any) => {
    if (Array.isArray(value)) {
        return value;
    } else {
        return !value || value === "" ? [] : JSON.parse(value);
    }
};

export function PocketSubcommunityChooser(props) {
    const componentName = "pocket-subcommunity-chooser";
    const initialValues = sanitizeValue(props.value);
    const [subcommunityIDs, setSubcommunityIDs] = useState(sanitizeValue(initialValues));

    console.log("props: ", props);
    console.log("initialValues: ", initialValues);
    console.log("subcommunityIDs: ", subcommunityIDs);
    console.log("componentExists(componentName): ", componentExists(componentName));
    console.log("getComponent(componentName); ", getComponent(componentName));

    // Must be after the useEffect and useState
    let MultiSubcommunityInput;
    if (componentExists(componentName)) {
        MultiSubcommunityInput = getComponent(componentName);
    } else {
        return null;
    }

    return (
        <DashboardFormGroup label={t("subcommunityIDs")} tag={"div"}>
            {/*<div className="input-wrap">*/}
            {/*    <MultiSubcommunityInput.Component*/}
            {/*        value={subcommunityIDs ?? []}*/}
            {/*        onChange={selectedSubCommunities => {*/}
            {/*            setSubcommunityIDs(*/}
            {/*                selectedSubCommunities.map(subCom => {*/}
            {/*                    return parseInt(subCom.value);*/}
            {/*                }),*/}
            {/*            );*/}
            {/*        }}*/}
            {/*    />*/}
            {/*</div>*/}
            {/*<input name={props.fieldName} type={"hidden"} value={JSON.stringify(subcommunityIDs)} />*/}
            {/*{!subcommunityIDs ||*/}
            {/*    (subcommunityIDs.length === 0 && <input name={props.fieldName + []} type={"hidden"} value={[]} />)}*/}
            {/*{subcommunityIDs.map((subComID, key) => {*/}
            {/*    return <input key={key} name={props.fieldName + "[]"} type={"hidden"} value={subComID} />;*/}
            {/*})}*/}
        </DashboardFormGroup>
    );
}
