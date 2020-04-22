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
import { ThemeBuilderUpload } from "@vanilla/library/src/scripts/forms/themeEditor/ThemeBuilderUpload";
import { ThemeBuilderSection } from "@vanilla/library/src/scripts/forms/themeEditor/ThemeBuilderSection";
import { ThemeDropDown } from "@vanilla/library/src/scripts/forms/themeEditor/ThemeDropDown";
import { SearchBarPresets, BannerAlignment, bannerVariables } from "@vanilla/library/src/scripts/banner/bannerStyles";
import { ThemeInputNumber } from "@vanilla/library/src/scripts/forms/themeEditor/ThemeInputNumber";
import { ButtonPreset } from "@vanilla/library/src/scripts/forms/buttonStyles";
import { ActivePanelChooser } from "@themingapi/theme/ActivePanelChooser";
import { ActiveVariablePanel } from "@themingapi/theme/ActivePanelContext";
import { ThemeBuilderSectionGroup } from "@vanilla/library/src/scripts/forms/themeEditor/ThemeBuilderSectionGroup";
import {
    ThemeBuilderBreakpoints,
    BreakpointViewType,
} from "@vanilla/library/src/scripts/forms/themeEditor/ThemeBuilderBreakpoints";
import { ThemeInputText } from "@library/forms/themeEditor/ThemeInputText";

export function ThemeBuilderSectionBanner() {
    return (
        <>
            <ActivePanelChooser titlePanel={ActiveVariablePanel.BANNER} />
            <ThemeBuilderBlock label={t("Banner Title")}>
                <ThemeInputText varKey={"banner.title.text"} debounceTime={false} />
            </ThemeBuilderBlock>
            <ThemeBuilderBlock label={t("Text Color")}>
                <ThemeColorPicker variableKey="banner.colors.primaryContrast" />
            </ThemeBuilderBlock>
            <ThemeBuilderBlock label={t("Alignment")}>
                <ThemeDropDown
                    variableKey="banner.options.alignment"
                    options={[
                        {
                            label: t("Left Aligned"),
                            value: BannerAlignment.LEFT,
                        },
                        {
                            label: t("Center Aligned"),
                            value: BannerAlignment.CENTER,
                        },
                    ]}
                />
            </ThemeBuilderBlock>
            <ThemeBuilderSectionGroup label={t("Background")}>
                <ThemeBuilderBlock label={t("Color")}>
                    <ThemeColorPicker variableKey="banner.colors.primary" />
                </ThemeBuilderBlock>
                <ThemeBuilderBlock label={t("Image")}>
                    <ThemeBuilderUpload variableKey="banner.outerBackground.image" />
                </ThemeBuilderBlock>
                <ThemeBuilderCheckBox label={t("Color Overlay")} variableKey="banner.backgrounds.useOverlay" />
                <ThemeBuilderBreakpoints
                    baseKey="banner.outerBackground"
                    responsiveKey="image"
                    enabledView={BreakpointViewType.IMAGE}
                ></ThemeBuilderBreakpoints>
            </ThemeBuilderSectionGroup>
            <ThemeBuilderSection label={t("Search")}>
                <ThemeBuilderBlock label={t("Preset")}>
                    <ThemeDropDown
                        variableKey="banner.presets.input.preset"
                        options={[
                            {
                                label: t("Borderless"),
                                value: SearchBarPresets.NO_BORDER,
                            },
                            {
                                label: t("Bordered"),
                                value: SearchBarPresets.BORDER,
                            },
                            {
                                label: t("Bordered (Unified)"),
                                value: SearchBarPresets.UNIFIED_BORDER,
                            },
                        ]}
                    />
                </ThemeBuilderBlock>
                <ThemeBuilderBlock label={t("Button Type")}>
                    <ThemeDropDown
                        variableKey="banner.presets.button.preset"
                        options={[
                            {
                                label: t("Transparent"),
                                value: ButtonPreset.TRANSPARENT,
                            },
                            {
                                label: t("Solid"),
                                value: ButtonPreset.SOLID,
                            },
                            {
                                label: t("Hidden"),
                                value: ButtonPreset.HIDE,
                            },
                        ]}
                    />
                </ThemeBuilderBlock>
                <ThemeBuilderBlock label={t("Background")}>
                    <ThemeColorPicker variableKey="banner.colors.bg" />
                </ThemeBuilderBlock>
                <ThemeBuilderBlock label={t("Text")}>
                    <ThemeColorPicker variableKey="banner.colors.fg" />
                </ThemeBuilderBlock>
                <ThemeBuilderBlock label={t("Border Radius")}>
                    <ThemeInputNumber
                        variableKey="banner.border.radius"
                        max={bannerVariables().searchBar.sizing.height / 2}
                    />
                </ThemeBuilderBlock>
            </ThemeBuilderSection>
        </>
    );
}
