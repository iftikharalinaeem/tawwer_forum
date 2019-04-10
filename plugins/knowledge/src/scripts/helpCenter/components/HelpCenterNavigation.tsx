/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { t } from "@library/utility/appUtils";
import React from "react";
import NavLinksWithHeadings from "@library/navigation/NavLinksWithHeadings";
import { ILinkListData } from "@library/@types/api/core";

/**
 * Component for rendering out a full set of knowledge base home data.
 */
export default class HelpCenterNavigation extends React.Component<IProps> {
    public render() {
        const { data, rootCategoryUrl } = this.props;
        const ungroupedCount = data.ungroupedItems || [];
        const groupedContent = data.groups || [];

        if (ungroupedCount.length !== 0 || groupedContent.length !== 0) {
            return (
                <NavLinksWithHeadings
                    title={t("Browse Articles by Category")}
                    accessibleViewAllMessage={t(`View all articles from category: "<0/>".`)}
                    data={data}
                    depth={2}
                    ungroupedTitle={t("Other Articles")}
                    ungroupedViewAllUrl={rootCategoryUrl}
                />
            );
        } else {
            return null;
        }
    }
}

interface IProps {
    data: ILinkListData;
    rootCategoryUrl?: string;
}
