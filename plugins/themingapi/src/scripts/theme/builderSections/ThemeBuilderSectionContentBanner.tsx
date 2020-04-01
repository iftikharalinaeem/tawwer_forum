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

export function ThemeBuilderSectionContentBanner() {
    return (
        <>
            <ActivePanelChooser titlePanel={ActiveVariablePanel.CONTENT_BANNER} />
            <ThemeBuilderCheckBox
                label={t("Enabled")}
                variableKey="contentBanner.options.enabled"
                info={t("The content banner appears on pages like discussions, articles, and search.")}
            />
            <ThemeBuilderBlock label={t("Background Image")}>
                <ThemeBuilderUpload variableKey="contentBanner.outerBackground.image" />
            </ThemeBuilderBlock>
            <ThemeBuilderCheckBox label={t("Color Overlay")} variableKey="contentBanner.backgrounds.useOverlay" />
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
        </>
    );
}
