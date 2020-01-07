/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useState } from "react";
import { t } from "@vanilla/i18n";
import { DashboardHeaderBlock } from "@dashboard/components/DashboardHeaderBlock";
import { BrowserRouter } from "react-router-dom";
import ThemePreviewCard from "@library/theming/ThemePreviewCard";
import CurrentTheme from "@themingapi/components/CurrentTheme";
import CurrentThemeInfo from "@library/theming/CurrentThemeInfo";

export default function ManageThemingPage(props) {
    const themePreviewDefault = {
        globalBg: "#fff",
        globalPrimary: "#985E6D",
        globalFg: "#555a62",
        titleBarBg: "#0291db",
        titleBarFg: "#fff",
        isActiveTheme: false,
    };
    const { currentTheme } = props;
    const {
        templateThemes = [
            { preview: themePreviewDefault },
            { preview: themePreviewDefault },
            { preview: themePreviewDefault },
            { preview: themePreviewDefault },
        ],
    } = props;
    const { customThemes = [] } = props;
    const {
        themeTemplatesStyles = {
            display: "flex",
            flexWrap: "wrap",
            justifyContent: "space-between",
        },
    } = props;

    return (
        <BrowserRouter>
            <CurrentTheme currentTheme={currentTheme} />

            <DashboardHeaderBlock title={t("Templates")} />
            <div style={themeTemplatesStyles}>
                {templateThemes.map((templateTheme, key) => (
                    <ThemePreviewCard key={key} {...templateTheme.preview} />
                ))}
            </div>
            <DashboardHeaderBlock title={t("Custom themes")} />
            {customThemes.map((theme, key) => (
                <ThemePreviewCard key={key} {...theme.preview} />
            ))}
        </BrowserRouter>
    );
}
