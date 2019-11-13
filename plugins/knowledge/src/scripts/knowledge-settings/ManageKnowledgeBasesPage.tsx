/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { DashboardTable } from "@dashboard/tables/DashboardTable";
import { TableColumnSize } from "@dashboard/tables/DashboardTableHeadItem";
import { KnowledgeBaseStatus, useKnowledgeBases } from "@knowledge/knowledge-bases/KnowledgeBaseModel";
import { KnowledgeBaseTableRow } from "@knowledge/knowledge-settings/KnowledgeBaseTableRow";
import Loader from "@library/loaders/Loader";
import qs from "qs";
import React from "react";
import { EmptyKnowledgeBasesResults } from "@knowledge/knowledge-settings/EmptyKnowledgeBasesResults";
import { t } from "@vanilla/i18n";
import { DashboardHeaderBlock } from "@dashboard/components/DashboardHeaderBlock";
import { KnowledgeBaseAddEdit } from "@knowledge/knowledge-settings/KnowledgeBaseAddEdit";
import { BrowserRouter } from "react-router-dom";
const { HeadItem } = DashboardTable;

export function ManageKnowledgeBasesPage() {
    const initialForm = qs.parse(window.location.search.replace(/^\?/, ""));
    const status = initialForm.status || KnowledgeBaseStatus.PUBLISHED;
    const kbs = useKnowledgeBases(status);

    if (!kbs.data) {
        return <Loader />;
    }

    return (
        <BrowserRouter>
            <DashboardHeaderBlock title={t("Knowledge Bases")} actionButtons={<KnowledgeBaseAddEdit />} />
            <DashboardTable
                head={
                    <tr>
                        <HeadItem>Knowledge Base</HeadItem>
                        <HeadItem>Locations</HeadItem>
                        <HeadItem size={TableColumnSize.XS}>Source Locale</HeadItem>
                        <HeadItem size={TableColumnSize.XS}>Options</HeadItem>
                    </tr>
                }
                body={Object.values(kbs.data).map(kb => (
                    <KnowledgeBaseTableRow key={kb.knowledgeBaseID} knowledgeBase={kb} forStatus={status} />
                ))}
            />
            {Object.entries(kbs.data).length === 0 && <EmptyKnowledgeBasesResults forStatus={status} />}
        </BrowserRouter>
    );
}
