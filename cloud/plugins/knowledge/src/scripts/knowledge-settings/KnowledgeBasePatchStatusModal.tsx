/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { useEffect, useCallback, useDebugValue } from "react";
import Modal from "@library/modal/Modal";
import ModalSizes from "@library/modal/ModalSizes";
import ModalConfirm from "@library/modal/ModalConfirm";
import { t } from "@vanilla/i18n";
import { useSelector } from "react-redux";
import { IKnowledgeAppStoreState } from "@knowledge/state/model";
import { useKnowledgeBaseActions } from "@knowledge/knowledge-bases/KnowledgeBaseActions";
import { LoadStatus } from "@library/@types/api/core";
import { KnowledgeBaseStatus } from "@knowledge/knowledge-bases/KnowledgeBaseModel";

interface IProps {
    knowledgeBaseID: number | null;
    newStatus: KnowledgeBaseStatus;
    onDismiss: () => void;
}

export function KnowledgeBasePatchStatusModal(props: IProps) {
    const { knowledgeBaseID, onDismiss } = props;
    const { patchKBStatus, clearPatchStatus } = useKnowledgeBaseActions();
    const { status } = usePatchStatus(props.knowledgeBaseID);

    const handleConfirm = async () => {
        if (knowledgeBaseID !== null) {
            await patchKBStatus(knowledgeBaseID, props.newStatus);
            clearPatchStatus({ kbID: knowledgeBaseID });
        }
    };

    const dismissModal = useCallback(() => {
        if (knowledgeBaseID !== null) {
            clearPatchStatus({ kbID: knowledgeBaseID });
        }
        onDismiss();
    }, [clearPatchStatus, knowledgeBaseID, onDismiss]);

    useEffect(() => {
        if (status === LoadStatus.SUCCESS) {
            dismissModal();
        }
    }, [dismissModal, status]);

    return (
        <ModalConfirm
            isVisible={knowledgeBaseID !== null}
            title={
                props.newStatus === KnowledgeBaseStatus.DELETED
                    ? t("Delete Knowledge Base")
                    : t("Restore Knowledge Base")
            }
            confirmTitle={t("Confirm")}
            onConfirm={handleConfirm}
            onCancel={dismissModal}
            isConfirmLoading={status === LoadStatus.LOADING}
        >
            {props.newStatus === KnowledgeBaseStatus.DELETED
                ? t("Are you sure you want to delete this knowledge base?")
                : t("Are you sure you want to restore this knowledge base?")}
        </ModalConfirm>
    );
}

function usePatchStatus(knowledgeBaseID: number | null) {
    const defaultStatus = {
        status: LoadStatus.PENDING,
    };

    const result =
        useSelector((state: IKnowledgeAppStoreState) => {
            return knowledgeBaseID !== null
                ? state.knowledge.knowledgeBases.patchStatusesByID[knowledgeBaseID]
                : knowledgeBaseID;
        }) ?? defaultStatus;

    useDebugValue(result);
    return result;
}
