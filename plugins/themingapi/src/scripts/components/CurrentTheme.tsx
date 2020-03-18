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
import { ButtonTypes } from "@vanilla/library/src/scripts/forms/buttonStyles";
import currentThemeClasses from "@vanilla/library/src/scripts/theming/currentThemeStyles";
import { IManageTheme, ThemeType } from "@vanilla/library/src/scripts/theming/ThemeActions";

interface IProps {
    currentTheme: IManageTheme;
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
                    <ThemePreviewCard
                        noActions={true}
                        globalPrimary={preview?.["global.mainColors.primary"] ?? undefined}
                        globalBg={preview?.["global.mainColors.bg"] ?? undefined}
                        globalFg={preview?.["global.mainColors.fg"] ?? undefined}
                        titleBarBg={
                            preview?.["global.mainColors.bg"] ?? preview?.["global.mainColors.primary"] ?? undefined
                        }
                        backgroundImage={preview?.["banner.outerBackground.image"] ?? undefined}
                        titleBarFg={preview?.["global.mainColors.fg"] ?? undefined}
                        previewImage={preview?.previewImage}
                        isActiveTheme={true}
                    />
                </div>
                <CurrentThemeInfo
                    {...currentTheme}
                    info={currentTheme.preview.info}
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
                />
            </div>
        </div>
    );
}
