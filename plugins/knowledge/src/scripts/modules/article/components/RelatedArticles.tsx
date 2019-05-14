/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import * as React from "react";
import { PanelWidget } from "@library/layout/PanelLayout";
import { t } from "@library/utility/appUtils";
import Heading from "@library/layout/Heading";
import { Link } from "react-router-dom";
import { panelListClasses } from "@library/layout/panelListStyles";
import classNames from "classnames";

export interface IInternalLink {
    name: string;
    to: string;
}

interface IProps {
    children: IInternalLink[];
}

/**
 * Implements the related articles component
 */
export default class RelatedArticles extends React.Component<IProps> {
    public render() {
        const classes = panelListClasses();
        if (this.props.children && this.props.children.length > 0) {
            const contents = this.props.children.map((item, i) => {
                return (
                    <li className={classNames("panelList-item", "relatedArticles-item")} key={"related-" + i}>
                        <Link
                            to={item.to}
                            className={classNames("panelList-link", classes.link, "relatedArticles-link")}
                            title={item.name}
                        >
                            {item.name}
                        </Link>
                    </li>
                );
            });

            return (
                <PanelWidget>
                    <nav className={classNames("panelList", "relatedArticles")}>
                        <Heading
                            title={t("Related Articles")}
                            className={classNames("panelList-title", "relatedArticles-title")}
                        />
                        <ul className={classNames("panelList-items", "relatedArticles-items")}>{contents}</ul>
                    </nav>
                </PanelWidget>
            );
        } else {
            return null;
        }
    }
}
