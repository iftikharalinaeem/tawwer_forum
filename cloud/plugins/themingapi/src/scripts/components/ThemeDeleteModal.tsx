/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { useEffect } from "react";
import ModalConfirm from "@library/modal/ModalConfirm";
import { t } from "@vanilla/i18n";
import { useSelector } from "react-redux";
import { LoadStatus } from "@library/@types/api/core";
import { IThemesStoreState } from "@library/theming/themeSettingsReducer";
import { useThemeActions } from "@vanilla/library/src/scripts/theming/ThemeActions";

interface IProps {
    themeID: number | string;
    onDismiss: () => void;
    elementToFocusOnExit?: HTMLElement;
    isVisible: boolean;
}

export function ThemeDeleteModal(props: IProps) {
    const { themeID, onDismiss, elementToFocusOnExit } = props;
    const { deleteTheme } = useThemeActions();
    const { status } = useDeleteStatus(props.themeID);

    const handleConfirm = async () => {
        deleteTheme(themeID);
    };

    useEffect(() => {
        if (status === LoadStatus.SUCCESS) {
            onDismiss();
        }
    }, [status, onDismiss]);

    return (
        <ModalConfirm
            isVisible={props.isVisible}
            title={t("Delete Theme")}
            confirmTitle={t("Delete")}
            onConfirm={handleConfirm}
            onCancel={props.onDismiss}
            isConfirmLoading={status === LoadStatus.LOADING}
            elementToFocusOnExit={elementToFocusOnExit}
        >
            {t("Are you sure want to delete this theme? It will be permanently deleted.")}
        </ModalConfirm>
    );
}

function useDeleteStatus(themeID: number | string) {
    return (
        useSelector((state: IThemesStoreState) => state.themeSettings.deleteThemeByID[themeID]) ?? {
            status: LoadStatus.PENDING,
        }
    );
}
