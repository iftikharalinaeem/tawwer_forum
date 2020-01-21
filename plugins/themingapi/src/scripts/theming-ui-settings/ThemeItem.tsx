/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IManageTheme, useThemesActions, PreviewStatusType } from "@library/theming/ThemesActions";
import ThemePreviewCard from "@library/theming/ThemePreviewCard";
import React, {useEffect} from "react";
import { t } from "@vanilla/i18n";
import { themeItemClasses } from "@themingapi/theming-ui-settings/themeItemStyles";
import { useThemeSettingsState } from "@library/theming/themeSettingsReducer";
import { LoadStatus } from "@library/@types/api/core";

interface IProps {
    theme: IManageTheme;
}

export function ThemeItem(props: IProps) {
    const { applyStatus, previewStatus } = useThemeSettingsState();
    const { putCurrentTheme, putPreviewTheme } = useThemesActions();
    const classes = themeItemClasses();
    const { preview } = props.theme;

    const handlePreview = async () => {
        putPreviewTheme({themeID: props.theme.themeID, type: PreviewStatusType.PREVIEW});
    }

    useEffect(() => {
        if (previewStatus.status === LoadStatus.SUCCESS) {
            window.location.href = '/';
        }
    })
    return (
        <div className={classes.item}>
            <ThemePreviewCard
                name={props.theme.name || t("Unknown Theme")}
                isActiveTheme={props.theme.current}
                onApply={() => {
                    putCurrentTheme(props.theme.themeID);
                }}
                onPreview={handlePreview}
                isApplyLoading={
                    applyStatus.status === LoadStatus.LOADING && applyStatus.data.themeID === props.theme.themeID
                }
                globalPrimary={preview?.["global.mainColors.primary"] ?? undefined}
                globalBg={preview?.["global.mainColors.bg"] ?? undefined}
                globalFg={preview?.["global.mainColors.fg"] ?? undefined}
                titleBarBg={preview?.["global.mainColors.bg"] ?? preview?.["global.mainColors.primary"] ?? undefined}
                titleBarFg={preview?.["global.mainColors.fg"] ?? undefined}
                previewImage={preview?.previewImage}
            />
            <h3 className={classes.title}>{props.theme.name}</h3>
        </div>
    );
}
