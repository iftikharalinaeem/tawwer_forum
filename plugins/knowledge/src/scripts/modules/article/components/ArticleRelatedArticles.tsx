/**
 * @author Stéphane (slafleche) LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import * as React from "react";
import { PanelWidget } from "@knowledge/layouts/PanelLayout";
import { t } from "@library/application";
import Heading from "@library/components/Heading";
import { Link } from "react-router-dom";
import { loopableArray } from "@library/utility";

export interface IInternalLink {
    name: string;
    to: string;
}

interface IProps {
    children: IInternalLink[];
}

export default class ArticleRelatedArticles extends React.Component<IProps> {
    public render() {
        if (loopableArray(this.props.children)) {
            const contents = this.props.children.map((item, i) => {
                return (
                    <li className="linkList-item relatedArticles-item" key={"related-" + i}>
                        <Link to={item.to} className="linkList-link relatedArticles-link" title={item.name}>
                            {item.name}
                        </Link>
                    </li>
                );
            });

            return (
                <PanelWidget>
                    <nav className="linkList relatedArticles">
                        <Heading title={t("Related Articles")} className="linkList-title relatedArticles-title" />
                        <ul className="linkList-items relatedArticles-items">{contents}</ul>
                    </nav>
                </PanelWidget>
            );
        } else {
            return null;
        }
    }
}
