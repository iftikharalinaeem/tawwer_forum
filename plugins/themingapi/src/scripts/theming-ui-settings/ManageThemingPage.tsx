/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { DashboardHeaderBlock } from "@dashboard/components/DashboardHeaderBlock";
import { LoadStatus } from "@library/@types/api/core";
import Loader from "@library/loaders/Loader";
import CurrentTheme from "@themingapi/components/CurrentTheme";
import { ThemeItem } from "@themingapi/theming-ui-settings/ThemeItem";
import { useThemesActions } from "@library/theming/ThemesActions";
import { useThemeSettingsState } from "@library/theming/themeSettingsReducer";
import { t } from "@vanilla/i18n";
import React, { useEffect } from "react";
import { BrowserRouter } from "react-router-dom";

export default function ManageThemingPage(props) {
    const themeSettingsState = useThemeSettingsState();
    const actions = useThemesActions();
    useEffect(() => {
        if (themeSettingsState.themes.status === LoadStatus.PENDING) {
            actions.getAllThemes();
        }
    });

    if (!themeSettingsState.themes.data || themeSettingsState.themes.status === LoadStatus.LOADING) {
        return <Loader />;
    }

    const { currentTheme, templates, themes } = themeSettingsState.themes.data;

    const {
        themeTemplatesStyles = {
            display: "flex",
            flexWrap: "wrap",
            justifyContent: "flex-start",
            padding: 24,
            marginLeft: -36,
            marginRight: -36,
        },
    } = props;

    return (
        <BrowserRouter>
            <CurrentTheme currentTheme={currentTheme || {}} />
            <div className="subheading-title">
                <h2 className="subheading-title">{t("Templates")}</h2>
                <div className="subheading-description">
                    {t("Templates are the standard themes. To customize them, you have to create a copy.")}
                </div>
            </div>
            <div style={themeTemplatesStyles}>
                {templates.map((templateTheme, key) => (
                    <ThemeItem key={key} theme={templateTheme} />
                ))}
            </div>
            {themes.length > 0 && (
                <>
                    <div className="subheading-title">
                        <h2 className="subheading-title">{t("Custom Themes")}</h2>
                        <div className="subheading-description">
                            {t(
                                "Custom Themes are the themes you created. You can edit, rename or make a copy of them.",
                            )}
                        </div>
                    </div>
                    <div style={themeTemplatesStyles}>
                        {themes.map((theme, key) => (
                            <ThemeItem key={key} theme={theme} />
                        ))}
                    </div>
                </>
            )}
        </BrowserRouter>
    );
}
