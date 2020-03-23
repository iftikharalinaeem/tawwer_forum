/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { t } from "@vanilla/i18n";
import React, { useState } from "react";
import { ThemeChooserInput } from "@library/theming/ThemeChooserInput";
import { DashboardFormGroup } from "@dashboard/forms/DashboardFormGroup";

interface IProps {
    // Gdn_Form version (uncontrolled).
    initialValue: number | string | null | boolean; // Gdn_Form can give us some nasty values.
    formFieldName: string;
}

export function SubcommunityThemeFormGroup(props: IProps) {
    const { formFieldName, initialValue } = props;

    const [value, setValue] = useState(initialValue && typeof initialValue !== "boolean" ? initialValue : "");

    const setTheme = (selectedTheme: string | number) => {
        setValue(selectedTheme);
    };

    return (
        <>
            <DashboardFormGroup label={t("Themes")} description={"Select a theme to be applied to this subcommunity."}>
                <input name={formFieldName} type="hidden" value={value != null ? value : ""} />
                <ThemeChooserInput initialValue={value} onChange={setTheme} value={value} />
            </DashboardFormGroup>
        </>
    );
}
