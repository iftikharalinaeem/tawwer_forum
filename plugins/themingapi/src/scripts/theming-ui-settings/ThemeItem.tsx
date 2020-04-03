/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IManageTheme, useThemeActions, PreviewStatusType } from "@vanilla/library/src/scripts/theming/ThemeActions";
import ThemePreviewCard from "@library/theming/ThemePreviewCard";
import React, { useEffect, useState } from "react";
import { t, translate } from "@vanilla/i18n";
import { useThemeSettingsState } from "@library/theming/themeSettingsReducer";
import { LoadStatus } from "@library/@types/api/core";
import { ThemeDeleteModal } from "@themingapi/components/ThemeDeleteModal";
import { ThemeEditorRoute } from "@themingapi/routes/themeEditorRoutes";
import classNames from "classnames";
import ModalConfirm from "@vanilla/library/src/scripts/modal/ModalConfirm";
import { getMeta } from "@vanilla/library/src/scripts/utility/appUtils";
import { InformationIcon } from "@vanilla/library/src/scripts/icons/common";
import { ToolTip, ToolTipIcon } from "@vanilla/library/src/scripts/toolTip/ToolTip";
import { ThemePreviewTitle } from "@vanilla/library/src/scripts/theming/ThemePreviewTitle";

interface IProps {
    theme: IManageTheme;
    className?: string;
}

export function ThemeItem(props: IProps) {
    const { applyStatus, previewStatus } = useThemeSettingsState();
    const { putCurrentTheme, putPreviewTheme } = useThemeActions();
    const [showMobileWarning, setShowMobileWarning] = useState(false);
    const { preview } = props.theme;
    const themeID = props.theme.themeID;
    const copyCustomTheme = props.theme.type === "themeDB" ? true : false;

    const applyTheme = () => {
        putCurrentTheme(props.theme.themeID);
        setShowMobileWarning(false);
        window.scrollTo({ top: 0, behavior: "smooth" });
    };

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
            {
                <ModalConfirm
                    isVisible={showMobileWarning}
                    title={t("Mobile Theme Change")}
                    onConfirm={applyTheme}
                    onCancel={() => {
                        setShowMobileWarning(false);
                    }}
                >
                    {t(
                        "Your mobile theme will be disabled.",
                        "Your mobile theme will be disabled. To set different mobile and desktop themes, use the old theming UI.",
                    )}
                </ModalConfirm>
            }
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
            <div className={classNames(props.className)}>
                <ThemePreviewCard
                    name={props.theme.name || t("Unknown Theme")}
                    isActiveTheme={props.theme.current}
                    onApply={() => {
                        if (getMeta("ui.desktopThemeKey") !== getMeta("ui.mobileThemeKey")) {
                            setShowMobileWarning(true);
                        } else {
                            applyTheme();
                        }
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
                <ThemePreviewTitle theme={props.theme} />
            </div>
        </>
    );
}
