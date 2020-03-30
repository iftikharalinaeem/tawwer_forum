/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { DashboardMediaItem } from "@dashboard/tables/DashboardMediaItem";
import { DashboardTable } from "@dashboard/tables/DashboardTable";
import { DashboardTableOptions } from "@dashboard/tables/DashboardTableOptions";
import { IKnowledgeBase, KnowledgeBaseStatus } from "@knowledge/knowledge-bases/KnowledgeBaseModel";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { DeleteIcon, EditIcon, WarningIcon } from "@library/icons/common";
import LinkAsButton from "@library/routing/LinkAsButton";
import { ToolTip, ToolTipIcon } from "@library/toolTip/ToolTip";
import { getMeta } from "@library/utility/appUtils";
import { t, LocaleDisplayer } from "@vanilla/i18n";
import React from "react";
import Button from "@library/forms/Button";
import { iconClasses } from "@library/icons/iconClasses";

interface IProps {
    knowledgeBase: IKnowledgeBase;
    forStatus: KnowledgeBaseStatus;
    onEditClick?: () => void;
    onStatusChangeClick: () => void;
    onPurgeClick?: () => void;
}
export function KnowledgeBaseTableRow(props: IProps) {
    const kb = props.knowledgeBase;

    const siteSectionLocales = kb.siteSections.map(siteSection => siteSection.contentLocale);
    const hasConflictingSourceLocale = !siteSectionLocales.includes(kb.sourceLocale);

    return (
        <tr>
            <td>
                <DashboardMediaItem title={kb.name} info={kb.description} imgSrc={kb.icon} />
            </td>
            <td>
                {kb.siteSections.length > 0 ? (
                    kb.siteSections.map(section => {
                        const sectionBase = section.basePath.replace("/", "");
                        const fullUrlCode = `${sectionBase ? "/" + sectionBase : ""}/kb/${kb.urlCode.replace("/", "")}`;
                        return (
                            <React.Fragment key={section.sectionID}>
                                <a href={getMeta("context.host") + fullUrlCode}>{fullUrlCode}</a>
                                <br />
                            </React.Fragment>
                        );
                    })
                ) : (
                    <ToolTip label={"This knowledge base is not accesssible due to a multisite configuration issue."}>
                        <ToolTipIcon>
                            <WarningIcon className={iconClasses().errorFgColor} />
                        </ToolTipIcon>
                    </ToolTip>
                )}
            </td>
            <td>
                {hasConflictingSourceLocale ? (
                    <ToolTip
                        label={
                            "The source locale of this knowledge base may not be accesssible due to a multisite configuration issue."
                        }
                    >
                        <ToolTipIcon>
                            <WarningIcon className={iconClasses().errorFgColor} />
                        </ToolTipIcon>
                    </ToolTip>
                ) : (
                    <LocaleDisplayer localeContent={kb.sourceLocale} displayLocale={kb.sourceLocale} />
                )}
            </td>
            <td>
                <DashboardTableOptions>
                    {props.onEditClick && (
                        <Button className="btn-icon" onClick={props.onEditClick} baseClass={ButtonTypes.ICON_COMPACT}>
                            <EditIcon />
                        </Button>
                    )}
                    {props.onPurgeClick && (
                        <Button
                            className="btn-icon"
                            onClick={props.onPurgeClick}
                            baseClass={ButtonTypes.DASHBOARD_LINK}
                        >
                            {t("Purge")}
                        </Button>
                    )}
                    {props.forStatus === KnowledgeBaseStatus.DELETED ? (
                        <Button
                            className="btn-icon"
                            onClick={props.onStatusChangeClick}
                            baseClass={ButtonTypes.TEXT_PRIMARY}
                        >
                            {t("Restore")}
                        </Button>
                    ) : (
                        <Button
                            className="btn-icon"
                            onClick={props.onStatusChangeClick}
                            baseClass={ButtonTypes.ICON_COMPACT}
                        >
                            <DeleteIcon />
                        </Button>
                    )}
                </DashboardTableOptions>
            </td>
        </tr>
    );
}
