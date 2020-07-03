/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import * as React from "react";
import { t } from "@library/utility/appUtils";
import Heading from "@library/layout/Heading";
import { mobileDropDownClasses } from "@library/headers/pieces/mobileDropDownStyles";
import classNames from "classnames";
import { panelListClasses } from "@library/layout/panelListStyles";
import { useLayout } from "@library/layout/LayoutContext";

interface IProps {
    children: React.ReactNode;
    hideTitle?: boolean;
}

/**
 * Implements the Article Revision History component
 */
export default class RevisionsList extends React.Component<IProps> {
    public render() {
        const classes = mobileDropDownClasses();
        const classesPanelList = panelListClasses(useLayout().mediaQueries);
        return (
            <div className="revisionsList related">
                {!this.props.hideTitle && (
                    <Heading
                        className={classNames(classesPanelList.title, "panelList-title", "itemList-title")}
                        title={t("Revisions")}
                        depth={2}
                    />
                )}
                <ul className={classNames("itemList-items", "panelList-items", classes.listContainer)}>
                    {this.props.children}
                </ul>
            </div>
        );
    }
}
