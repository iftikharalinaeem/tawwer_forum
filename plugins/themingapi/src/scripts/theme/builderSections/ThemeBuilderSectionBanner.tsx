/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { ThemeBuilderTitle } from "@vanilla/library/src/scripts/forms/themeEditor/ThemeBuilderTitle";
import { t } from "@vanilla/i18n";
import { ThemeBuilderBlock } from "@vanilla/library/src/scripts/forms/themeEditor/ThemeBuilderBlock";
import { ThemeColorPicker } from "@vanilla/library/src/scripts/forms/themeEditor/ThemeColorPicker";
import { ThemeBuilderCheckBox } from "@vanilla/library/src/scripts/forms/themeEditor/ThemeBuilderCheckBox";

export function ThemeBuilderSectionBanner() {
    return (
        <>
            <ThemeBuilderTitle title={t("Banner")} />
            <ThemeBuilderCheckBox label={t("Color Overlay")} variableKey="compactSearch.backgrounds.useOverlay" />
            <ThemeBuilderBlock label={t("Background Color")}>
                <ThemeColorPicker variableKey="banner.colors.primary" />
            </ThemeBuilderBlock>
            <ThemeBuilderBlock label={t("Text")}>
                <ThemeColorPicker variableKey="banner.colors.primaryContrast" />
            </ThemeBuilderBlock>
        </>
    );
}
