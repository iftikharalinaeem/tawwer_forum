/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { ThemeBuilderTitle } from "@vanilla/library/src/scripts/forms/themeEditor/ThemeBuilderTitle";
import { t } from "@vanilla/i18n";
import { ThemeBuilderBlock } from "@vanilla/library/src/scripts/forms/themeEditor/ThemeBuilderBlock";
import { ThemeColorPicker } from "@vanilla/library/src/scripts/forms/themeEditor/ThemeColorPicker";
import { ThemeInputNumber } from "@vanilla/library/src/scripts/forms/themeEditor/ThemeInputNumber";
import { ThemeDropDown } from "@vanilla/library/src/scripts/forms/themeEditor/ThemeDropDown";
import { BorderType } from "@vanilla/library/src/scripts/styles/styleHelpers";
import { ThemeBuilderSection } from "@vanilla/library/src/scripts/forms/themeEditor/ThemeBuilderSection";
import { LogoAlignment } from "@vanilla/library/src/scripts/headers/TitleBar";
import { ThemeBuilderCheckBox } from "@vanilla/library/src/scripts/forms/themeEditor/ThemeBuilderCheckBox";
import { ThemeBuilderUpload } from "@vanilla/library/src/scripts/forms/themeEditor/ThemeBuilderUpload";
import { useThemeBuilder } from "@vanilla/library/src/scripts/forms/themeEditor/ThemeBuilderContext";
import { ActivePanelChooser } from "@themingapi/theme/ActivePanelChooser";
import { ActiveVariablePanel } from "@themingapi/theme/ActivePanelContext";
import { ThemeInputText } from "@vanilla/library/src/scripts/forms/themeEditor/ThemeInputText";

export function ThemeBuilderSectionTitleBar() {
    const { rawThemeVariables, setVariableValue } = useThemeBuilder();
    return (
        <>
            <ActivePanelChooser titlePanel={ActiveVariablePanel.TITLE_BAR} />
            <ThemeBuilderBlock label={t("Background Color")}>
                <ThemeColorPicker variableKey="titleBar.colors.bg" />
            </ThemeBuilderBlock>
            <ThemeBuilderCheckBox
                label={t("Full Bleed")}
                variableKey="titleBar.fullBleed.enabled"
                info={t("When you select Full Bleed, your background is transparent.")}
            />
            <ThemeBuilderBlock label={t("Text")}>
                <ThemeColorPicker variableKey="titleBar.colors.fg" />
            </ThemeBuilderBlock>
            <ThemeBuilderBlock label={t("Height")}>
                <ThemeInputNumber min={48} max={88} step={2} variableKey="titleBar.sizing.height" />
            </ThemeBuilderBlock>
            <ThemeBuilderBlock label={t("Style")}>
                <ThemeDropDown
                    variableKey="titleBar.border.type"
                    options={[
                        {
                            label: t("Borderless"),
                            value: BorderType.NONE,
                        },
                        {
                            label: t("Bordered"),
                            value: BorderType.BORDER,
                        },
                        {
                            label: t("Shadowed"),
                            value: BorderType.SHADOW,
                        },
                    ]}
                />
            </ThemeBuilderBlock>
            <ThemeBuilderSection label={t("Logo")}>
                <ThemeBuilderBlock label={t("Image")}>
                    <ThemeBuilderUpload variableKey="titleBar.logo.desktop.url" />
                </ThemeBuilderBlock>
                <ThemeBuilderBlock label={t("Alignment")}>
                    <ThemeDropDown
                        variableKey="titleBar.logo.justifyContent"
                        afterChange={value => {
                            if (value === LogoAlignment.CENTER) {
                                setVariableValue("titleBar.navAlignment.alignment", undefined);
                            }
                        }}
                        options={[
                            {
                                label: t("Left Aligned"),
                                value: LogoAlignment.LEFT,
                            },
                            {
                                label: t("Center Aligned"),
                                value: LogoAlignment.CENTER,
                            },
                        ]}
                    />
                </ThemeBuilderBlock>
                <ThemeBuilderBlock label={t("Image (Mobile)")}>
                    <ThemeBuilderUpload variableKey="titleBar.logo.mobile.url" />
                </ThemeBuilderBlock>
                <ThemeBuilderBlock label={t("Alignment (Mobile)")}>
                    <ThemeDropDown
                        variableKey="titleBar.mobileLogo.justifyContent"
                        options={[
                            {
                                label: t("Left Aligned"),
                                value: LogoAlignment.LEFT,
                            },
                            {
                                label: t("Center Aligned"),
                                value: LogoAlignment.CENTER,
                            },
                        ]}
                    />
                </ThemeBuilderBlock>
                <ThemeBuilderBlock label={t("Url")}>
                    <ThemeInputText placeholder="https://" varKey={"navigation.logo.url"} />
                </ThemeBuilderBlock>
            </ThemeBuilderSection>
            <ThemeBuilderSection label={t("Navigation")}>
                <ThemeBuilderBlock label={t("Alignment")}>
                    <ThemeDropDown
                        disabled={rawThemeVariables?.titleBar?.logo?.justifyContent === LogoAlignment.CENTER}
                        variableKey="titleBar.navAlignment.alignment"
                        options={[
                            {
                                label: t("Left Aligned"),
                                value: "left",
                            },
                            {
                                label: t("Center Aligned"),
                                value: "center",
                            },
                        ]}
                    />
                </ThemeBuilderBlock>
            </ThemeBuilderSection>
        </>
    );
}
