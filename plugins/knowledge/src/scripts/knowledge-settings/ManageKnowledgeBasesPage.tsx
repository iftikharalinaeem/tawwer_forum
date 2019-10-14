/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { DashboardMediaItem } from "@dashboard/tables/DashboardMediaItem";
import { DashboardTable } from "@dashboard/tables/DashboardTable";
import { TableColumnSize } from "@dashboard/tables/DashboardTableHeadItem";
import { DashboardTableOptions } from "@dashboard/tables/DashboardTableOptions";
import { useKnowledgeBases, IKnowledgeBase } from "@knowledge/knowledge-bases/KnowledgeBaseModel";
import { ButtonTypes } from "@library/forms/buttonStyles";
import { DeleteIcon, EditIcon } from "@library/icons/common";
import Loader from "@library/loaders/Loader";
import LinkAsButton from "@library/routing/LinkAsButton";
import { t } from "@vanilla/i18n";
import React from "react";
const { HeadItem } = DashboardTable;

export function ManageKnowledgeBasesPage() {
    const kbs = useKnowledgeBases();

    if (!kbs.data) {
        return <Loader />;
    }

    return (
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
                <KnowledgeBaseRow key={kb.knowledgeBaseID} knowledgeBase={kb} />
            ))}
        />
    );
}

interface IProps {
    knowledgeBase: IKnowledgeBase;
}
function KnowledgeBaseRow(props: IProps) {
    const kb = props.knowledgeBase;
    return (
        <tr key={kb.knowledgeBaseID}>
            <td>
                <DashboardMediaItem title={kb.name} info={kb.description} imgSrc={kb.icon} />
            </td>
            <td>
                <a href={kb.url}>/{kb.urlCode}</a>
            </td>
            <td>{kb.sourceLocale}</td>
            <td>
                <DashboardTableOptions>
                    <LinkAsButton
                        to={`/knowledge-settings/knowledge-bases/${kb.knowledgeBaseID}/edit`}
                        className="js-modal btn-icon"
                        baseClass={ButtonTypes.ICON_COMPACT}
                    >
                        <EditIcon />
                    </LinkAsButton>
                    <LinkAsButton
                        to={`/knowledge-settings/knowledge-bases/${kb.knowledgeBaseID}/delete`}
                        className="js-modal-confirm btn-icon"
                        data-body={t(
                            "Are you sure you want to delete this knowledge base? It can restored later from the Deleted Knowledge Bases page.",
                        )}
                        baseClass={ButtonTypes.ICON_COMPACT}
                    >
                        <DeleteIcon />
                    </LinkAsButton>
                </DashboardTableOptions>
            </td>
        </tr>
    );
}
