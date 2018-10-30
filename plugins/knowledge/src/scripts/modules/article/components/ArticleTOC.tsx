/**
 * @author Stéphane (slafleche) LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import * as React from "react";
import { PanelWidget } from "@knowledge/layouts/PanelLayout";
import { t } from "@library/application";
import Heading from "@library/components/Heading";
import { IOutlineItem } from "@knowledge/@types/api";

interface IProps {
    items: IOutlineItem[];
}

/**
 * Implements the table of contents component
 */
export default class ArticleTOC extends React.Component<IProps> {
    private static readonly minimumChildrenCount = 2;

    public render() {
        if (this.props.items.length < ArticleTOC.minimumChildrenCount) {
            return null;
        }

        const contents = this.props.items.map(item => {
            return (
                <li className="panelList-item tableOfContents-item" key={item.ref}>
                    <a href={"#" + item.ref} className="tableOfContents-link" title={item.text}>
                        {item.text}
                    </a>
                </li>
            );
        });

        return (
            <PanelWidget>
                <nav className="panelList tableOfContents">
                    <Heading title={t("Table of Contents")} className="panelList-title tableOfContents-title" />
                    <ul className="panelList-items tableOfContents-items">{contents}</ul>
                </nav>
            </PanelWidget>
        );
    }
}
