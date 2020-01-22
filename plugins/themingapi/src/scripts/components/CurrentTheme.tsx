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
    const { currentTheme } = props;
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
                <ThemePreviewCard noActions={true} {...currentTheme.preview} />
                <CurrentThemeInfo {...currentTheme} info={currentTheme.preview.info} />
            </div>
        </div>
    );
}
