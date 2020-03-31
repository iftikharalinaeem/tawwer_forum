/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { t } from "@vanilla/i18n";
import React, { useState } from "react";
import { ThemeChooserInput } from "@library/theming/ThemeChooserInput";
import { DashboardFormGroup } from "@dashboard/forms/DashboardFormGroup";
import Translate from "@library/content/Translate";

interface IProps {
    // Gdn_Form version (uncontrolled).
    initialValue: number | string | null | boolean; // Gdn_Form can give us some nasty values.
    formFieldName: string;
}

export function SubcommunityThemeFormGroup(props: IProps) {
    const { formFieldName, initialValue } = props;

    const [value, setValue] = useState(initialValue && typeof initialValue !== "boolean" ? initialValue : "");

    return (
        <>
            <DashboardFormGroup
                label={t("Themes")}
                description={
                    <Translate
                        source="Choose a theme for this subcommunity.  <0>Read More</0>"
                        c0={text => (
                            <a href="https://success.vanillaforums.com/kb/articles/205-per-subcommunity-themes">
                                {text}
                            </a>
                        )}
                    />
                }
            >
                <input name={formFieldName} type="hidden" value={value != null ? value : ""} />
                <ThemeChooserInput initialValue={value} onChange={setValue} value={value} />
            </DashboardFormGroup>
        </>
    );
}
