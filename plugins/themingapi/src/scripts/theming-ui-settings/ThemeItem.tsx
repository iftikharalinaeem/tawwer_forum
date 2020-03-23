/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IManageTheme, useThemeActions, PreviewStatusType } from "@vanilla/library/src/scripts/theming/ThemeActions";
import ThemePreviewCard from "@library/theming/ThemePreviewCard";
import React, { useEffect, useState } from "react";
import { t } from "@vanilla/i18n";
import { themeItemClasses } from "@themingapi/theming-ui-settings/themeItemStyles";
import { useThemeSettingsState } from "@library/theming/themeSettingsReducer";
import { LoadStatus } from "@library/@types/api/core";
import { ThemeDeleteModal } from "@themingapi/components/ThemeDeleteModal";
import { ThemeEditorRoute } from "@themingapi/routes/themeEditorRoutes";
import classNames from "classnames";

interface IProps {
    theme: IManageTheme;
    className?: string;
}

export function ThemeItem(props: IProps) {
    const { applyStatus, previewStatus } = useThemeSettingsState();
    const { putCurrentTheme, putPreviewTheme } = useThemeActions();
    const classes = themeItemClasses();
    const { preview } = props.theme;
    const themeID = props.theme.themeID;
    const copyCustomTheme = props.theme.type === "themeDB" ? true : false;

    const handlePreview = async () => {
        putPreviewTheme({ themeID: props.theme.themeID, type: PreviewStatusType.PREVIEW });
    };

    useEffect(() => {
        if (previewStatus.status === LoadStatus.SUCCESS) {
            window.location.href = "/";
        }
    });
    const [deleteID, setDeleteID] = useState<number | string | null>(null);

    return (
        <>
            {deleteID !== null && (
                <ThemeDeleteModal
                    isVisible={deleteID !== null}
                    themeID={deleteID}
                    onDismiss={() => {
                        setDeleteID(null);
                    }}
                    elementToFocusOnExit={document.body}
                />
            )}
            <div className={classNames(classes.item, props.className)}>
                <ThemePreviewCard
                    name={props.theme.name || t("Unknown Theme")}
                    isActiveTheme={props.theme.current}
                    onApply={() => {
                        putCurrentTheme(props.theme.themeID);
                        window.scrollTo({ top: 0, behavior: "smooth" });
                    }}
                    isApplyLoading={
                        applyStatus.status === LoadStatus.LOADING && applyStatus.data?.themeID === props.theme.themeID
                    }
                    onEdit={ThemeEditorRoute.url({ themeID: themeID })}
                    onCopy={ThemeEditorRoute.url({ templateName: themeID })}
                    onPreview={handlePreview}
                    globalPrimary={preview?.["global.mainColors.primary"] ?? undefined}
                    globalBg={preview?.["global.mainColors.bg"] ?? undefined}
                    globalFg={preview?.["global.mainColors.fg"] ?? undefined}
                    titleBarBg={preview?.["global.mainColors.primary"] ?? undefined}
                    backgroundImage={preview?.["banner.outerBackground.image"] ?? undefined}
                    titleBarFg={preview?.["global.mainColors.fg"] ?? undefined}
                    previewImage={preview?.previewImage}
                    canCopy={props.theme.type !== "themeDB"}
                    canDelete={props.theme.type === "themeDB"}
                    canEdit={props.theme.type === "themeDB"}
                    onDelete={() => {
                        setDeleteID(props.theme.themeID);
                    }}
                    canCopyCustom={copyCustomTheme}
                />
                <h3 className={classes.title}>{props.theme.name}</h3>
            </div>
        </>
    );
}
