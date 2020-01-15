/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IManageTheme, useThemesActions } from "@themingapi/theming-ui-settings/ThemesActions";
import ThemePreviewCard from "@library/theming/ThemePreviewCard";
import React from "react";
import { t } from "@vanilla/i18n";
import { themeItemClasses } from "@themingapi/theming-ui-settings/themeItemStyles";
import { useThemeSettingsState } from "@themingapi/theming-ui-settings/themeSettingsReducer";
import { LoadStatus } from "@library/@types/api/core";

interface IProps {
    theme: IManageTheme;
}

export function ThemeItem(props: IProps) {
    const { applyStatus } = useThemeSettingsState();
    const { putCurrentTheme } = useThemesActions();
    const classes = themeItemClasses();
    const { preview } = props.theme;

    return (
        <div className={classes.item}>
            <ThemePreviewCard
                name={props.theme.name || t("Unknown Theme")}
                isActiveTheme={props.theme.current}
                onApply={() => {
                    putCurrentTheme(props.theme.themeID);
                }}
                isApplyLoading={
                    applyStatus.status === LoadStatus.LOADING && applyStatus.data?.themeID === props.theme.themeID
                }
                globalPrimary={preview?.["global.mainColors.primary"] ?? undefined}
                globalBg={preview?.["global.mainColors.bg"] ?? undefined}
                globalFg={preview?.["global.mainColors.fg"] ?? undefined}
                titleBarBg={preview?.["global.mainColors.bg"] ?? preview?.["global.mainColors.primary"] ?? undefined}
                titleBarFg={preview?.["global.mainColors.fg"] ?? undefined}
                previewImage={preview?.previewImage}
                themeType ={props.theme.type}
            />
            <h3 className={classes.title}>{props.theme.name}</h3>
        </div>
    );
}
