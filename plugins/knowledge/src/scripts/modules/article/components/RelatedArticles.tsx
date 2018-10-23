/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import * as React from "react";
import { PanelWidget } from "@knowledge/layouts/PanelLayout";
import { t } from "@library/application";
import Heading from "@library/components/Heading";
import { Link } from "react-router-dom";

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
        if (this.props.children && this.props.children.length > 0) {
            const contents = this.props.children.map((item, i) => {
                return (
                    <li className="related-item relatedArticles-item" key={"related-" + i}>
                        <Link to={item.to} className="related-link relatedArticles-link" title={item.name}>
                            {item.name}
                        </Link>
                    </li>
                );
            });

            return (
                <PanelWidget>
                    <nav className="related relatedArticles">
                        <Heading title={t("Related Articles")} className="related-title relatedArticles-title" />
                        <ul className="related-items relatedArticles-items">{contents}</ul>
                    </nav>
                </PanelWidget>
            );
        } else {
            return null;
        }
    }
}
