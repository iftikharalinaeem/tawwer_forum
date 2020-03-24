/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { LoadStatus } from "@library/@types/api/core";
import Loader from "@library/loaders/Loader";
import CurrentTheme from "@themingapi/components/CurrentTheme";
import { ThemeItem } from "@themingapi/theming-ui-settings/ThemeItem";
import { useThemeActions } from "@vanilla/library/src/scripts/theming/ThemeActions";
import { useThemeSettingsState } from "@library/theming/themeSettingsReducer";
import { t } from "@vanilla/i18n";
import React, { useEffect } from "react";
import { BrowserRouter } from "react-router-dom";
import { AddTheme } from "@vanilla/library/src/scripts/theming/AddTheme";
import { ThemeEditorRoute } from "@themingapi/routes/themeEditorRoutes";
import { PlusIcon } from "@vanilla/library/src/scripts/icons/common";
import { manageThemingClasses } from "@themingapi/theming-ui-settings/manageThemingStyles";
import { themeItemClasses } from "@themingapi/theming-ui-settings/themeItemStyles";
import { DashboardHelpAsset } from "@dashboard/forms/DashboardHelpAsset";
import SmartLink from "@vanilla/library/src/scripts/routing/links/SmartLink";

export default function ManageThemingPage(props) {
    const themeSettingsState = useThemeSettingsState();
    const actions = useThemeActions();

    useEffect(() => {
        if (themeSettingsState.themes.status === LoadStatus.PENDING) {
            actions.getAllThemes();
        }
    });

    const helpAsset = (
        <DashboardHelpAsset>
            <h3>{t("Heads Up!")}</h3>
            <p>
                {t(
                    "Welcome to Vanilla's theming UI.",
                    "Welcome to Vanilla's theming UI. This page lists all of your available themes, and allows you to copy or edit them.",
                )}
            </p>
            <p>
                {t(
                    "Some older themes don't support full editing capability.",
                    "Some older themes don't support full editing capability. To see what a theme supports you can hover over its name to see where edits will take effect.",
                )}
            </p>
            <h3>{t("Need More Help?")}</h3>
            <p>
                <SmartLink to={"https://success.vanillaforums.com/kb/theme-guide"}>{t("Theming Guide")}</SmartLink>
            </p>
            <h3>{t("Old Theming UI")}</h3>
            <p>
                {t(
                    "If you have an old theme",
                    "If you have an old theme and need to set a separate desktop and mobile theme you can do so with the old theming UI.",
                )}
            </p>
            <p>
                <SmartLink to={"/settings/themes"}>{t("Old Theming UI")}</SmartLink>
            </p>
        </DashboardHelpAsset>
    );

    if (!themeSettingsState.themes.data || themeSettingsState.themes.status === LoadStatus.LOADING) {
        return (
            <>
                <Loader />
                {helpAsset}
            </>
        );
    }

    const { currentTheme, templates, themes } = themeSettingsState.themes.data;
    const classes = manageThemingClasses();

    return (
        <BrowserRouter>
            {helpAsset}
            <CurrentTheme currentTheme={currentTheme || {}} />
            <div className="subheading-title">
                <h2 className="subheading-title">{t("Templates")}</h2>
                <div className="subheading-description">
                    {t("Templates are the standard themes. To customize them, you have to create a copy.")}
                </div>
            </div>
            <div className={classes.grid}>
                {templates.map((templateTheme, key) => (
                    <ThemeItem key={key} theme={templateTheme} className={classes.gridItem} />
                ))}
            </div>
            <div className="subheading-title">
                <h2 className="subheading-title">{t("Custom Themes")}</h2>
                <div className="subheading-description">
                    {t("Custom Themes are the themes you created. You can edit, rename or make a copy of them.")}
                </div>
            </div>
            <div className={classes.grid}>
                {themes.map((theme, key) => (
                    <ThemeItem key={key} theme={theme} className={classes.gridItem} />
                ))}

                <div className={classes.gridItem}>
                    <AddTheme
                        className={classes.gridItem}
                        onAdd={
                            <ThemeEditorRoute.Link data={{ newTheme: true }}>
                                <PlusIcon />
                            </ThemeEditorRoute.Link>
                        }
                    />
                    <h3 className={themeItemClasses().title} aria-hidden={true}>
                        &nbsp;
                    </h3>
                </div>
            </div>
        </BrowserRouter>
    );
}
