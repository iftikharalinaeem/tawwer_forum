/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { t } from "@library/utility/appUtils";
import React from "react";
import NavLinksWithHeadings from "@library/navigation/NavLinksWithHeadings";
import { ILinkListData } from "@library/@types/api/core";
import { INavLinkNoItemComponentProps } from "@vanilla/library/src/scripts/navigation/NavLinks";
import { EditorRoute } from "@knowledge/routes/pageRoutes";
import { IKbNavigationItem, KbRecordType } from "@knowledge/navigation/state/NavigationModel";
import Translate from "@vanilla/library/src/scripts/content/Translate";
import Permission from "@vanilla/library/src/scripts/features/users/Permission";
import classNames from "classnames";
import { navLinksClasses } from "@vanilla/library/src/scripts/navigation/navLinksStyles";
import { KbPermission } from "@knowledge/knowledge-bases/KbPermission";

interface IProps {
    data: ILinkListData;
    rootCategory: IKbNavigationItem<KbRecordType.CATEGORY>;
    kbID: number;
    title?: string;
    showTitle?: boolean;
}

/**
 * Component for rendering out a full set of knowledge base home data.
 */
export default function HelpCenterNavigation(props: IProps) {
    const { data, rootCategory } = props;

    function KbNoNavItems(innerProps: INavLinkNoItemComponentProps) {
        return (
            <span>
                {t("This category does not have any articles.")}
                <KbPermission permission={"articles.add"} kbID={props.kbID}>
                    <EditorRoute.Link
                        className={classNames(innerProps.className, navLinksClasses().noItemLink)}
                        data={{ knowledgeBaseID: props.kbID, knowledgeCategoryID: innerProps.recordID }}
                    >
                        {t("Create Article", "Create an Article")}.
                    </EditorRoute.Link>
                </KbPermission>
            </span>
        );
    }
    const ungroupedCount = data.ungroupedItems || [];
    const groupedContent = data.groups || [];

    if (ungroupedCount.length !== 0 || groupedContent.length !== 0) {
        return (
            <NavLinksWithHeadings
                title={props.title ?? t("Browse Articles by Category")}
                showTitle={props.showTitle}
                accessibleViewAllMessage={t(`View all articles from category: "<0/>".`)}
                data={data}
                depth={2}
                ungroupedTitle={t("Other Articles")}
                ungroupedViewAllUrl={rootCategory.url}
                NoItemsComponent={KbNoNavItems}
            />
        );
    } else {
        return null;
    }
}
