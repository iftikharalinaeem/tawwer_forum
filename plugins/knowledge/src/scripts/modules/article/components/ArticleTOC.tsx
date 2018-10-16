/**
 * @author Stéphane (slafleche) LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import * as React from "react";
import { PanelWidget } from "@knowledge/layouts/PanelLayout";
import { t } from "@library/application";
import Heading from "@library/components/Heading";

export interface IPageHeading {
    name: string;
    anchor: string;
}

interface IProps {
    children?: IPageHeading[];
    minimumChildrenCount?: number;
}

/**
 * Implements the table of contents component
 */
export default class ArticleTOC extends React.Component<IProps> {
    public static defaultProps = {
        minimumChildrenCount: 2,
    };

    public render() {
        if (
            !!this.props.children &&
            this.props.children.length > 0 &&
            this.props.children.length >= this.props.minimumChildrenCount!
        ) {
            const contents = this.props.children.map((item, i) => {
                return (
                    <li className="related-item tableOfContents-item" key={"toc-" + i}>
                        <a href={item.anchor} className="tableOfContents-link" title={item.name}>
                            {item.name}
                        </a>
                    </li>
                );
            });

            return (
                <PanelWidget>
                    <nav className="related tableOfContents">
                        <Heading title={t("Table of Contents")} className="related-title tableOfContents-title" />
                        <ul className="related-items tableOfContents-items">{contents}</ul>
                    </nav>
                </PanelWidget>
            );
        } else {
            return null;
        }
    }
}
