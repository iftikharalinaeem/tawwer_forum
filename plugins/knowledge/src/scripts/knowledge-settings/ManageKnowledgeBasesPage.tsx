/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { DashboardTable } from "@dashboard/tables/DashboardTable";
import { TableColumnSize } from "@dashboard/tables/DashboardTableHeadItem";
import { KnowledgeBaseStatus } from "@knowledge/knowledge-bases/KnowledgeBaseModel";
import { KnowledgeBaseTableRow } from "@knowledge/knowledge-settings/KnowledgeBaseTableRow";
import Loader from "@library/loaders/Loader";
import qs from "qs";
import React, { useState } from "react";
import { EmptyKnowledgeBasesResults } from "@knowledge/knowledge-settings/EmptyKnowledgeBasesResults";
import { t } from "@vanilla/i18n";
import { DashboardHeaderBlock } from "@dashboard/components/DashboardHeaderBlock";
import { KnowledgeBaseAddEdit } from "@knowledge/knowledge-settings/addEdit/KnowledgeBaseAddEdit";
import { BrowserRouter } from "react-router-dom";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { useKnowledgeBaseActions } from "@knowledge/knowledge-bases/KnowledgeBaseActions";
import { KnowledgeBasePatchStatusModal } from "@knowledge/knowledge-settings/KnowledgeBasePatchStatusModal";
import { KnowledgeBasePurgeModal } from "@knowledge/knowledge-settings/KnowledgeBasePurgeModal";
import { useKnowledgeBases } from "@knowledge/knowledge-bases/knowledgeBaseHooks";
const { HeadItem } = DashboardTable;

export function ManageKnowledgeBasesPage() {
    const initialForm = qs.parse(window.location.search.replace(/^\?/, ""));
    const status = initialForm.status || KnowledgeBaseStatus.PUBLISHED;
    const kbs = useKnowledgeBases(status);
    const { initForm } = useKnowledgeBaseActions();
    const [isFormOpen, setIsFormOpen] = useState(false);
    const [editingID, setEditingID] = useState<number | null>(null);
    const [statusChangeID, setStatusChangeID] = useState<number | null>(null);
    const [purgeID, setPurgeID] = useState<number | null>(null);

    const closeForm = () => {
        setIsFormOpen(false);
        setEditingID(null);
    };
    const toggleButtonRef = React.createRef<HTMLButtonElement>();

    if (!kbs.data) {
        return <Loader />;
    }

    return (
        <BrowserRouter>
            <DashboardHeaderBlock
                title={status === KnowledgeBaseStatus.PUBLISHED ? t("Knowledge Bases") : t("Deleted Knowledge Bases")}
                showBackLink={status === KnowledgeBaseStatus.DELETED}
                actionButtons={
                    status === KnowledgeBaseStatus.PUBLISHED && (
                        <Button
                            buttonRef={toggleButtonRef}
                            baseClass={ButtonTypes.DASHBOARD_PRIMARY}
                            onClick={() => setIsFormOpen(true)}
                        >
                            {t("Add Knowledge Base")}
                        </Button>
                    )
                }
            />
            {isFormOpen && (
                <KnowledgeBaseAddEdit
                    kbID={editingID ?? undefined}
                    onClose={() => {
                        closeForm();
                        toggleButtonRef.current?.focus();
                    }}
                />
            )}
            <KnowledgeBasePatchStatusModal
                newStatus={
                    // Switching to the opposite status
                    status === KnowledgeBaseStatus.DELETED ? KnowledgeBaseStatus.PUBLISHED : KnowledgeBaseStatus.DELETED
                }
                knowledgeBaseID={statusChangeID}
                onDismiss={() => {
                    setStatusChangeID(null);
                }}
            />
            <KnowledgeBasePurgeModal
                knowledgeBaseID={purgeID}
                onDismiss={() => {
                    setPurgeID(null);
                }}
            />
            <DashboardTable
                head={
                    <tr>
                        <HeadItem>Knowledge Base</HeadItem>
                        <HeadItem>Locations</HeadItem>
                        <HeadItem size={TableColumnSize.XS}>Source Locale</HeadItem>
                        <HeadItem size={TableColumnSize.XS}>Options</HeadItem>
                    </tr>
                }
                body={Object.values(kbs.data)
                    .filter(kb => kb.status === status)
                    .map(kb => (
                        <KnowledgeBaseTableRow
                            key={kb.knowledgeBaseID}
                            knowledgeBase={kb}
                            forStatus={status}
                            onEditClick={
                                status === KnowledgeBaseStatus.PUBLISHED
                                    ? () => {
                                          setEditingID(kb.knowledgeBaseID);
                                          initForm({ kbID: kb.knowledgeBaseID });
                                          setIsFormOpen(true);
                                      }
                                    : undefined
                            }
                            onPurgeClick={
                                status === KnowledgeBaseStatus.DELETED
                                    ? () => {
                                          setPurgeID(kb.knowledgeBaseID);
                                      }
                                    : undefined
                            }
                            onStatusChangeClick={() => {
                                setStatusChangeID(kb.knowledgeBaseID);
                            }}
                        />
                    ))}
            />
            {Object.entries(kbs.data).length === 0 && <EmptyKnowledgeBasesResults forStatus={status} />}
        </BrowserRouter>
    );
}
