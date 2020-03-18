/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { ThemeBuilderTitle } from "@vanilla/library/src/scripts/forms/themeEditor/ThemeBuilderTitle";
import { t } from "@vanilla/i18n";
import { ThemeBuilderBlock } from "@vanilla/library/src/scripts/forms/themeEditor/ThemeBuilderBlock";
import { ThemeDropDown } from "@vanilla/library/src/scripts/forms/themeEditor/ThemeDropDown";
import { GlobalPreset } from "@vanilla/library/src/scripts/styles/globalStyleVars";
import { ThemeColorPicker } from "@vanilla/library/src/scripts/forms/themeEditor/ThemeColorPicker";
import { ThemeBuilderSection } from "@vanilla/library/src/scripts/forms/themeEditor/ThemeBuilderSection";
import { GoogleFontDropdown } from "@themingapi/theme/GoogleFontDropdown";
import { ThemeInputNumber } from "@vanilla/library/src/scripts/forms/themeEditor/ThemeInputNumber";
import { ThemeBuilderButtonSection } from "@themingapi/theme/builderSections/ThemeBuilderButtonSection";

export function ThemeBuilderSectionGlobal() {
    return (
        <>
            <ThemeBuilderTitle title={t("Global Styles")} />
            <ThemeBuilderBlock label={t("Preset")}>
                <ThemeDropDown
                    // This is actually an array, but the first is the real one. The rest are fallbacks.
                    variableKey="global.options.preset"
                    options={[
                        { label: t("Light"), value: GlobalPreset.LIGHT },
                        { label: t("Dark"), value: GlobalPreset.DARK },
                    ]}
                ></ThemeDropDown>
            </ThemeBuilderBlock>
            <ThemeBuilderBlock label={t("Brand Color")}>
                <ThemeColorPicker variableKey={"global.mainColors.primary"} />
            </ThemeBuilderBlock>
            <ThemeBuilderSection label={t("Body")}>
                <ThemeBuilderBlock label={t("Background")}>
                    <ThemeColorPicker variableKey="global.mainColors.bg" />
                </ThemeBuilderBlock>
                <ThemeBuilderBlock label={t("Text")}>
                    <ThemeColorPicker variableKey="global.mainColors.fg" />
                </ThemeBuilderBlock>
                <ThemeBuilderBlock label={t("Links")}>
                    <ThemeColorPicker variableKey="global.links.colors.default" />
                </ThemeBuilderBlock>
                <ThemeBuilderBlock label={t("Font")}>
                    <GoogleFontDropdown />
                </ThemeBuilderBlock>
            </ThemeBuilderSection>
            <ThemeBuilderSection label={t("Buttons & Inputs")}>
                <ThemeBuilderBlock label={t("Border Radius")}>
                    <ThemeInputNumber variableKey="global.border.radius" />
                </ThemeBuilderBlock>
                <ThemeBuilderButtonSection label={t("Primary Buttons")} buttonType="primary" />
                <ThemeBuilderButtonSection label={t("Secondary Buttons")} buttonType="standard" />
            </ThemeBuilderSection>
        </>
    );
}
