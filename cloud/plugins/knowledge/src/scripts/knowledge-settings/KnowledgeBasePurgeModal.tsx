/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { useEffect } from "react";
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
    onDismiss: () => void;
}

export function KnowledgeBasePurgeModal(props: IProps) {
    const { knowledgeBaseID, onDismiss } = props;
    const { deleteKB, clearDeleteStatus } = useKnowledgeBaseActions();
    const { status } = useDeleteStatus(props.knowledgeBaseID);

    const handleConfirm = async () => {
        if (knowledgeBaseID !== null) {
            deleteKB({ kbID: knowledgeBaseID });
        }
    };

    useEffect(() => {
        if (status === LoadStatus.SUCCESS && knowledgeBaseID) {
            onDismiss();
            clearDeleteStatus({ kbID: knowledgeBaseID });
        }
    }, [status, onDismiss, knowledgeBaseID, clearDeleteStatus]);

    return (
        <ModalConfirm
            isVisible={knowledgeBaseID !== null}
            title={t("Purge Knowledge Base")}
            confirmTitle={t("Purge")}
            onConfirm={handleConfirm}
            onCancel={props.onDismiss}
            isConfirmLoading={status === LoadStatus.LOADING}
        >
            {t("Are you sure you want to purge this knowledge base?")}
        </ModalConfirm>
    );
}

function useDeleteStatus(knowledgeBaseID: number | null) {
    const defaultStatus = {
        status: LoadStatus.PENDING,
    };

    return (
        useSelector((state: IKnowledgeAppStoreState) => {
            return knowledgeBaseID !== null
                ? state.knowledge.knowledgeBases.deletesByID[knowledgeBaseID]
                : knowledgeBaseID;
        }) ?? defaultStatus
    );
}
