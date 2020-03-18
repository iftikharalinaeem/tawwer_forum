/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { ThemeBuilderSectionGroup } from "@vanilla/library/src/scripts/forms/themeEditor/ThemeBuilderSectionGroup";
import { t } from "@vanilla/i18n";
import { ThemeBuilderBlock } from "@vanilla/library/src/scripts/forms/themeEditor/ThemeBuilderBlock";
import { ThemeDropDown } from "@vanilla/library/src/scripts/forms/themeEditor/ThemeDropDown";
import { ButtonPreset } from "@vanilla/library/src/scripts/forms/buttonStyles";
import { ThemeColorPicker } from "@vanilla/library/src/scripts/forms/themeEditor/ThemeColorPicker";

interface IProps {
    label: string;
    buttonType: string;
}

export function ThemeBuilderButtonSection(props: IProps) {
    const prefix = `button.${props.buttonType}.preset`;
    return (
        <ThemeBuilderSectionGroup label={props.label}>
            <ThemeBuilderBlock label={t("Button Type")}>
                <ThemeDropDown
                    variableKey={`${prefix}.style`}
                    options={[
                        {
                            label: t("Solid"),
                            value: ButtonPreset.SOLID,
                        },
                        {
                            label: t("Outline"),
                            value: ButtonPreset.OUTLINE,
                        },
                    ]}
                ></ThemeDropDown>
            </ThemeBuilderBlock>
            <ThemeBuilderBlock label={t("Background")}>
                <ThemeColorPicker variableKey={`${prefix}.bg`} />
            </ThemeBuilderBlock>
            <ThemeBuilderBlock label={t("Text")}>
                <ThemeColorPicker variableKey={`${prefix}.fg`} />
            </ThemeBuilderBlock>
        </ThemeBuilderSectionGroup>
    );
}
