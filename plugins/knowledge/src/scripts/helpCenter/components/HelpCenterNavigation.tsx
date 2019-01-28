/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IHelpData, NavArticle } from "@knowledge/modules/navigation/NavigationSelector";
import { t } from "@library/application";
import React from "react";
import NavLinksWithHeadings from "@library/components/NavLinksWithHeadings";

/**
 * Component for rendering out a full set of knowledge base home data.
 */
export default class HelpCenterNavigation extends React.Component<IProps> {
    public render() {
        const { data } = this.props;
        const ungroupedCount = data.ungroupedArticles || [];
        const groupedContent = data.groups || [];

        if (ungroupedCount.length !== 0 || groupedContent.length !== 0) {
            return <NavLinksWithHeadings title={t("Browse Articles by Category")} linkGroups={data} />;
        } else {
            return null;
        }
    }
}

interface IProps {
    data: IHelpData;
}
