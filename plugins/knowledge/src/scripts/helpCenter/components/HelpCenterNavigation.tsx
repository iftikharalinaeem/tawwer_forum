/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { t } from "@library/application";
import React from "react";
import NavLinksWithHeadings from "@library/components/NavLinksWithHeadings";
import { ILinkListData } from "@library/@types/api";
import NavLinks from "library/src/scripts/components/NavLinks";

/**
 * Component for rendering out a full set of knowledge base home data.
 */
export default class HelpCenterNavigation extends React.Component<IProps> {
    public render() {
        const { data } = this.props;
        const ungroupedCount = data.ungroupedItems || [];
        const groupedContent = data.groups || [];

        if (ungroupedCount.length !== 0 || groupedContent.length !== 0) {
            return (
                <NavLinksWithHeadings
                    title={t("Browse Articles by Category")}
                    accessibleViewAllMessage={t(`View all articles from category: "<0/>".`)}
                    data={data}
                    depth={2}
                />
            );
        } else {
            return null;
        }
    }
}

interface IProps {
    data: ILinkListData;
}
