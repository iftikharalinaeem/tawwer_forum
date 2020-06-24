/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import { ActivePanelChooser } from "@themingapi/theme/ActivePanelChooser";
import { ActiveVariablePanel } from "@themingapi/theme/ActivePanelContext";
import { t } from "@vanilla/i18n";
import { BannerAlignment } from "@vanilla/library/src/scripts/banner/bannerStyles";
import { ThemeBuilderBlock } from "@vanilla/library/src/scripts/forms/themeEditor/ThemeBuilderBlock";
import { ThemeBuilderCheckBox } from "@vanilla/library/src/scripts/forms/themeEditor/ThemeBuilderCheckBox";
import { ThemeBuilderUpload } from "@vanilla/library/src/scripts/forms/themeEditor/ThemeBuilderUpload";
import { ThemeDropDown } from "@vanilla/library/src/scripts/forms/themeEditor/ThemeDropDown";
import React from "react";
import { ThemeToggle } from "@vanilla/library/src/scripts/forms/themeEditor/ThemeToggle";
import { ThemeColorPicker } from "@vanilla/library/src/scripts/forms/themeEditor/ThemeColorPicker";
import { ThemeInputNumber } from "@vanilla/library/src/scripts/forms/themeEditor/ThemeInputNumber";
import { ThemeBuilderSectionGroup } from "@vanilla/library/src/scripts/forms/themeEditor/ThemeBuilderSectionGroup";
import { ThemeBuilderSection } from "@vanilla/library/src/scripts/forms/themeEditor/ThemeBuilderSection";
import { useThemeBuilder } from "@vanilla/library/src/scripts/forms/themeEditor/ThemeBuilderContext";
import {
    ThemeBuilderBreakpoints,
    BreakpointViewType,
} from "@vanilla/library/src/scripts/forms/themeEditor/ThemeBuilderBreakpoints";

export function ThemeBuilderSectionContentBanner() {
    const { generatedThemeVariables } = useThemeBuilder();
    return (
        <>
            <ActivePanelChooser titlePanel={ActiveVariablePanel.CONTENT_BANNER} />
            <ThemeBuilderBlock label="Content Banner" info={t("The content banner appears below the title bar.")}>
                <ThemeToggle variableKey="contentBanner.options.enabled" />
            </ThemeBuilderBlock>
            <ThemeBuilderBlock label={t("Height")}>
                <ThemeInputNumber variableKey="contentBanner.dimensions.minHeight" max={240} />
            </ThemeBuilderBlock>
            <ThemeBuilderBlock label={t("Height (Mobile)")}>
                <ThemeInputNumber variableKey="contentBanner.dimensions.mobile.minHeight" max={180} />
            </ThemeBuilderBlock>
            <ThemeBuilderSectionGroup label={t("Background")}>
                <ThemeBuilderBlock label={t("Color")}>
                    <ThemeColorPicker variableKey="contentBanner.outerBackground.color" />
                </ThemeBuilderBlock>
                <ThemeBuilderBlock label={t("Image")}>
                    <ThemeBuilderUpload variableKey="contentBanner.outerBackground.image" />
                </ThemeBuilderBlock>
                <ThemeBuilderCheckBox label={t("Color Overlay")} variableKey="contentBanner.backgrounds.useOverlay" />
                {generatedThemeVariables.titleBar.fullBleed.enabled && (
                    <ThemeBuilderCheckBox
                        label={t("Full Bleed")}
                        variableKey="contentBanner.options.overlayTitleBar"
                        info={t('Configure the Content Banner to work with the "Full Bleed" Title Bar option.')}
                    />
                )}
                <ThemeBuilderBreakpoints
                    baseKey="contentBanner.outerBackground"
                    responsiveKey="image"
                    enabledView={BreakpointViewType.IMAGE}
                ></ThemeBuilderBreakpoints>
            </ThemeBuilderSectionGroup>
            <ThemeBuilderSection label={t("Logo")}>
                <ThemeBuilderBlock label={t("Image")}>
                    <ThemeBuilderUpload variableKey="contentBanner.logo.image" />
                </ThemeBuilderBlock>
                <ThemeBuilderBlock label={t("Alignment")}>
                    <ThemeDropDown
                        variableKey="contentBanner.options.alignment"
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
            </ThemeBuilderSection>
        </>
    );
}
