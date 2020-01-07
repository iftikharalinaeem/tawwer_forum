/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useState } from "react";
import { t } from "@vanilla/i18n";
import { DashboardHeaderBlock } from "@dashboard/components/DashboardHeaderBlock";
import { BrowserRouter } from "react-router-dom";
import ThemePreviewCard from "@library/theming/ThemePreviewCard";
import CurrentThemeInfo from "@library/theming/CurrentThemeInfo";

export default function CurrentTheme(props) {
    const themePreviewDefault = {
        globalBg: "#fff",
        globalPrimary: "#985E6D",
        globalFg: "#555a62",
        titleBarBg: "#0291db",
        titleBarFg: "#fff",
        isActiveTheme: false,
    };
    const {
        currentTheme = {
            name: "Success Theme",
            authors: "Author",
            description: "A responsive VanillaTheme with customisation for the header and footer",
            preview: themePreviewDefault,
        },
    } = props;
    const {
        currentThemeStyles = {
            display: "flex",
            backgroundColor: "#f6f9fb",
            padding: 23,
            marginLeft: -18,
            marginRight: -18,
        },
    } = props;

    return (
        <div>
            <DashboardHeaderBlock title={t("Themes")} />
            <div style={currentThemeStyles}>
                <ThemePreviewCard {...currentTheme.preview} />
                <CurrentThemeInfo {...currentTheme} />
            </div>
        </div>
    );
}
