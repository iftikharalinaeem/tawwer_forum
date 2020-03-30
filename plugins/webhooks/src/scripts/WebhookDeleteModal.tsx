/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { useEffect, useCallback, useDebugValue } from "react";
import ModalConfirm from "@library/modal/ModalConfirm";
import { t } from "@vanilla/i18n";
import { useSelector } from "react-redux";
import { IWebhookState } from "@webhooks/WebhookTypes";
import { useWebhookActions } from "@webhooks/WebhookActions";
import { LoadStatus } from "@library/@types/api/core";


interface IProps {
    webhookID: number | null;
    onDismiss: () => void;
}

export function WebhookDeleteModal(props: IProps) {
    const { webhookID, onDismiss } = props;
    const { deleteWebhook, clearDeleteStatus } = useWebhookActions();
    const { status } = useDeleteStatus(props.webhookID);

    const handleConfirm = async () => {
        if (webhookID !== null) {
            deleteWebhook({ webhookID: webhookID });
            onDismiss();
        }
    };

    useEffect(() => {
        if (status === LoadStatus.SUCCESS && webhookID) {
            onDismiss();
            clearDeleteStatus({ webhookID: webhookID });
        }
    }, [status, onDismiss, webhookID, clearDeleteStatus]);

    return (
        <ModalConfirm
            isVisible={webhookID !== null}
            title={t("Delete webhook")}
            confirmTitle={t("Delete")}
            onConfirm={handleConfirm}
            onCancel={props.onDismiss}
            isConfirmLoading={status === LoadStatus.LOADING}
        >
            {t("Are you sure you want to delete this webhook?")}
        </ModalConfirm>
    );
}

function useDeleteStatus(webhookID: number | null) {
    const defaultStatus = {
        status: LoadStatus.PENDING,
    };

    return (
        useSelector((state: IWebhookState) => {
            return webhookID !== null
                ? state.webhooksByID
                : webhookID;
        }) ?? defaultStatus
    );
}
