/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { useEffect, useCallback, useDebugValue } from "react";
import Modal from "@library/modal/Modal";
import ModalSizes from "@library/modal/ModalSizes";
import ModalConfirm from "@library/modal/ModalConfirm";
import { t } from "@vanilla/i18n";
import { useSelector } from "react-redux";
import { IWebhooksStoreState } from "@webhooks/WebhookTypes";
import { useWebhookActions } from "@webhooks/WebhookActions";
import { LoadStatus } from "@library/@types/api/core";
import { WebhookStatus } from "@webhooks/WebhookTypes";
import { IWebhookState } from "./WebhookTypes";

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
                ? state.deletesByID
                : webhookID;
        }) ?? defaultStatus
    );
}

// interface IProps {
//     webhookID: number | null;
//     onDismiss: () => void;
//     newStatus: WebhookStatus;
// }

// export function WebhooksDeleteModal(props: IProps) {
//     const { webhookID, onDismiss } = props;
//     const { deleteWebhookStatus, clearDeleteStatus } = useWebhookActions();
//     const { status } = usePatchStatus(props.webhookID);

//     const handleConfirm = async () => {
//         if (webhookID !== null) {
//             await deleteWebhookStatus(webhookID);
//             clearDeleteStatus({ webhookID: webhookID });
//         }
//     };

//     const dismissModal = useCallback(() => {
//         if (webhookID !== null) {
//             clearDeleteStatus({ webhookID: webhookID });
//         }
//         onDismiss();
//     }, [clearDeleteStatus, webhookID, onDismiss]);

//     useEffect(() => {
//         if (status === LoadStatus.SUCCESS) {
//             dismissModal();
//         }
//     }, [dismissModal, status]);

//     return (
//         <ModalConfirm
//             isVisible={webhookID !== null}
//             title={
//                 props.newStatus === WebhookStatus.DELETED
//                     ? t("Delete Knowledge Base")
//                     : t("Restore Knowledge Base")
//             }
//             confirmTitle={t("Confirm")}
//             onConfirm={handleConfirm}
//             onCancel={dismissModal}
//             isConfirmLoading={status === LoadStatus.LOADING}
//         >
//             {props.newStatus === WebhookStatus.DELETED
//                 ? t("Are you sure you want to delete this knowledge base?")
//                 : t("Are you sure you want to restore this knowledge base?")}
//         </ModalConfirm>
//     );
// }

// function usePatchStatus(webhookID: number | null) {
//     const defaultStatus = {
//         status: LoadStatus.PENDING,
//     };

//     const result = 
//         useSelector((state: IWebhookStoreState) => {
//             return webhookID !== null
//                 ? state.webhooks.deletesByID[webhookID]
//                 : webhookID;
//         }) ?? defaultStatus;

//    useDebugValue(result);
//     return result;
// }
