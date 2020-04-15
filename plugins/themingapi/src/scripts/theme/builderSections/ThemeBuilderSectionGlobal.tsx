/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { useState } from "react";
import { t } from "@vanilla/i18n";
import { ThemeBuilderBlock } from "@vanilla/library/src/scripts/forms/themeEditor/ThemeBuilderBlock";
import { ThemeDropDown } from "@vanilla/library/src/scripts/forms/themeEditor/ThemeDropDown";
import { GlobalPreset } from "@vanilla/library/src/scripts/styles/globalStyleVars";
import { ThemeColorPicker } from "@vanilla/library/src/scripts/forms/themeEditor/ThemeColorPicker";
import { ThemeBuilderSection } from "@vanilla/library/src/scripts/forms/themeEditor/ThemeBuilderSection";
import { ThemeInputNumber } from "@vanilla/library/src/scripts/forms/themeEditor/ThemeInputNumber";
import { ThemeBuilderButtonSection } from "@themingapi/theme/builderSections/ThemeBuilderButtonSection";
import { buttonGlobalVariables } from "@vanilla/library/src/scripts/forms/buttonStyles";
import { ActivePanelChooser } from "@themingapi/theme/ActivePanelChooser";
import { ActiveVariablePanel } from "@themingapi/theme/ActivePanelContext";
import { ThemeBuilderFontBlock } from "@vanilla/library/src/scripts/forms/themeEditor/ThemeBuilderFontBlock";

export function ThemeBuilderSectionGlobal() {
    return (
        <>
            <ActivePanelChooser titlePanel={ActiveVariablePanel.GLOBAL} />
            <ThemeBuilderBlock label={t("Preset")}>
                <ThemeDropDown
                    // This is actually an array, but the first is the real one. The rest are fallbacks.
                    variableKey="global.options.preset"
                    options={[
                        { label: t("Light"), value: GlobalPreset.LIGHT },
                        { label: t("Dark"), value: GlobalPreset.DARK },
                    ]}
                />
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
                <ThemeBuilderFontBlock />
            </ThemeBuilderSection>

            <ThemeBuilderBlock label={t("Border Radius")}>
                <ThemeInputNumber
                    variableKey="global.borderType.formElements.buttons.radius"
                    max={buttonGlobalVariables().sizing.minHeight / 2}
                />
            </ThemeBuilderBlock>

            <ThemeBuilderButtonSection label={t("Primary Buttons")} buttonType="primary" />
            <ThemeBuilderButtonSection label={t("Secondary Buttons")} buttonType="standard" />
        </>
    );
}
