/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { DashboardMediaItem } from "@dashboard/tables/DashboardMediaItem";
import { DashboardTable } from "@dashboard/tables/DashboardTable";
import { TableColumnSize } from "@dashboard/tables/DashboardTableHeadItem";
import { DashboardTableOptions } from "@dashboard/tables/DashboardTableOptions";
import { useKnowledgeBases, IKnowledgeBase, KnowledgeBaseStatus } from "@knowledge/knowledge-bases/KnowledgeBaseModel";
import { ButtonTypes } from "@library/forms/buttonStyles";
import { DeleteIcon, EditIcon, AlertIcon } from "@library/icons/common";
import Loader from "@library/loaders/Loader";
import LinkAsButton from "@library/routing/LinkAsButton";
import { t } from "@vanilla/i18n";
import React from "react";
import qs from "qs";
import { ToolTip } from "@library/toolTip/ToolTip";
import { getMeta } from "@library/utility/appUtils";
const { HeadItem } = DashboardTable;

export function ManageKnowledgeBasesPage() {
    const initialForm = qs.parse(window.location.search.replace(/^\?/, ""));
    const kbs = useKnowledgeBases(initialForm.status || KnowledgeBaseStatus.PUBLISHED);

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

    const siteSectionLocales = kb.siteSections.map(siteSection => siteSection.contentLocale);
    const hasConflictingSourceLocale = !siteSectionLocales.includes(kb.sourceLocale);

    return (
        <tr>
            <td>
                <DashboardMediaItem title={kb.name} info={kb.description} imgSrc={kb.icon} />
            </td>
            <td>
                {kb.siteSections.map(section => {
                    const sectionBase = section.basePath.replace("/", "");
                    const fullUrlCode = `${sectionBase ? "/" + sectionBase : ""}/kb/${kb.urlCode.replace("/", "")}`;
                    return (
                        <React.Fragment key={section.sectionID}>
                            <a href={getMeta("context.host") + fullUrlCode}>{fullUrlCode}</a>
                            <br />
                        </React.Fragment>
                    );
                })}
            </td>
            <td>
                {hasConflictingSourceLocale ? (
                    <ToolTip
                        label={
                            "The source locale of this knowledge base may not be accesssible due to a multisite configuration issue."
                        }
                    >
                        <span>
                            <AlertIcon title={null} />
                        </span>
                    </ToolTip>
                ) : (
                    kb.sourceLocale
                )}
            </td>
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
