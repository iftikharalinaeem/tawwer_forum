/**
 * @author Stéphane (slafleche) LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import * as React from "react";
import { PanelWidget } from "@library/components/layouts/PanelLayout";
import { t } from "@library/application";
import Heading from "@library/components/Heading";
import { IOutlineItem } from "@knowledge/@types/api";
import classNames from "classnames";

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
            const href = "#" + item.ref;
            const isActive = window.location.hash === href;
            return (
                <li className={classNames("panelList-item", "tableOfContents-item", { isActive })} key={item.ref}>
                    <a href={href} onClick={this.forceHashChange} className="tableOfContents-link" title={item.text}>
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

    /**
     * @inheritdoc
     */
    public componentDidMount() {
        window.addEventListener("hashchange", this.handleHashChange);
    }

    /**
     * @inheritdoc
     */
    public componentWillUnmount() {
        window.removeEventListener("hashchange", this.handleHashChange);
    }

    private handleHashChange = () => {
        this.forceUpdate();
    };

    /**
     * Force a hash change event to occur.
     *
     * This is so that clicking a hash link __always__ results in scrolling to that link.
     */
    private forceHashChange = () => {
        window.dispatchEvent(new Event("hashchange"));
    };
}
