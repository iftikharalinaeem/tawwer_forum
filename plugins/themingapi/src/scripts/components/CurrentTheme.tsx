/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useState } from "react";
import { t } from "@vanilla/i18n";
import { DashboardHeaderBlock } from "@dashboard/components/DashboardHeaderBlock";
import ThemePreviewCard from "@library/theming/ThemePreviewCard";
import CurrentThemeInfo from "@library/theming/CurrentThemeInfo";
import { ThemeEditorRoute } from "@themingapi/routes/themeEditorRoutes";
import LinkAsButton from "@vanilla/library/src/scripts/routing/LinkAsButton";
import { ButtonTypes } from "@library/forms/buttonTypes";
import currentThemeClasses from "@vanilla/library/src/scripts/theming/currentThemeStyles";
import { ThemeType, ITheme } from "@vanilla/library/src/scripts/theming/themeReducer";

interface IProps {
    currentTheme: ITheme;
}

export default function CurrentTheme(props: IProps) {
    const classes = currentThemeClasses();
    const { currentTheme } = props;
    const { preview } = currentTheme;
    return (
        <div>
            <DashboardHeaderBlock title={t("Themes")} />
            <div className={classes.root}>
                <div className={classes.cardContainer}>
                    <ThemePreviewCard noActions={true} preview={preview} isActiveTheme={true} />
                </div>
                <CurrentThemeInfo
                    theme={currentTheme}
                    editButton={
                        currentTheme.type === ThemeType.DB ? (
                            <LinkAsButton
                                className={classes.themeActionButton}
                                baseClass={ButtonTypes.PRIMARY}
                                to={ThemeEditorRoute.url({ themeID: props.currentTheme.themeID })}
                            >
                                {t("Edit")}
                            </LinkAsButton>
                        ) : (
                            undefined
                        )
                    }
                    copyButton={
                        <LinkAsButton
                            className={classes.themeActionButton}
                            to={ThemeEditorRoute.url({ templateName: props.currentTheme.themeID })}
                        >
                            {t("Copy")}
                        </LinkAsButton>
                    }
                    revisionHistoryButton={
                        currentTheme.type === ThemeType.DB ? (
                            <LinkAsButton
                                className={classes.themeActionButton}
                                to={`/theme/theme-settings/${props.currentTheme.themeID}/revisions`}
                            >
                                {t("Revision History")}
                            </LinkAsButton>
                        ) : (
                            undefined
                        )
                    }
                />
            </div>
        </div>
    );
}
