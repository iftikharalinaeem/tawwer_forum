/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { IComboBoxOption } from "@library/features/search/SearchBar";
import SelectOne, { IMenuPlacement, MenuPlacement } from "@library/forms/select/SelectOne";
import { t } from "@vanilla/i18n";
import React, { useEffect, useState } from "react";
import { ThemeChooserInput } from "@library/theming/ThemeChooserInput";
import { DashboardFormGroup, useFormGroup } from "@dashboard/forms/DashboardFormGroup";
import { useSubcommunities } from "@subcommunities/subcommunities/subcommunitySelectors";
import { IFieldError, LoadStatus } from "@library/@types/api/core";
import { DashboardSelect } from "@dashboard/forms/DashboardSelect";
import { useThemeSettingsState } from "@library/theming/themeSettingsReducer";
import { useThemeActions } from "@library/theming/ThemeActions";
import { DashboardLabelType } from "@dashboard/forms/DashboardFormLabel";
import Loader from "@library/loaders/Loader";

interface IProps extends IMenuPlacement {
    // Gdn_Form version (uncontrolled).
    initialValue: number | string | null | boolean; // Gdn_Form can give us some nasty values.
    formFieldName: string;

    // Controlled react component
    value?: string | string | null;
    onChange?: (newValue: string | string | null) => void;
}

export function SubcommunityThemeFormGroup(props: IProps) {
    const { formFieldName, initialValue } = props;
    const [value, setValue] = useState();

    const setTheme = (selectedTheme: string | number | undefined | boolean) => {
        setValue(selectedTheme);
    };

    return (
        <>
            <DashboardFormGroup label={t("Themes")} description={"Select a theme to be applied to this subcommunity."}>
                <input name={formFieldName} type="hidden" value={value != null ? value : ""} />
                <ThemeChooserInput initialValue={initialValue} onChange={setTheme} value={undefined} />
            </DashboardFormGroup>
        </>
    );
}
