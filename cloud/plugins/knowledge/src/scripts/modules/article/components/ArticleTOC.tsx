/**
 * @author Stéphane (slafleche) LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import * as React from "react";
import { t } from "@library/utility/appUtils";
import Heading from "@library/layout/Heading";
import classNames from "classnames";
import { IOutlineItem } from "@knowledge/@types/api/article";
import { panelListClasses } from "@library/layout/panelListStyles";
import { articleTOCClasses } from "@knowledge/modules/article/components/articleTOCStyles";
import { uniqueIDFromPrefix } from "@library/utility/idUtils";
import { useLayout } from "@library/layout/LayoutContext";

interface IProps {
    items: IOutlineItem[];
}

/**
 * Implements the table of contents component
 */
export default class ArticleTOC extends React.Component<IProps> {
    private static readonly MINIMUM_CHILD_COUNT = 1;

    public render() {
        if (this.props.items.length < ArticleTOC.MINIMUM_CHILD_COUNT) {
            return null;
        }
        const classes = articleTOCClasses();
        const classesPanelList = panelListClasses(useLayout().mediaQueries);
        const contents = this.props.items
            .filter(item => item.level === 2)
            .map(item => {
                const href = "#" + item.ref;
                const isActive = window.location.hash === href;
                return (
                    <li
                        className={classNames("panelList-item", classesPanelList.item, classes.item, {
                            isActive,
                        })}
                        key={item.ref}
                    >
                        <a href={href} onClick={this.forceHashChange} className={classes.link} title={item.text}>
                            {item.text}
                        </a>
                    </li>
                );
            });
        const titleID = uniqueIDFromPrefix("articleTOC");

        return (
            <nav
                aria-labelledby={titleID}
                className={classNames("panelList", "tableOfContents", classesPanelList.root)}
            >
                <Heading
                    id={titleID}
                    title={t("On This Page")}
                    className={classNames(classesPanelList.title, "panelList-title", "tableOfContents-title")}
                />
                <ul className={classNames("panelList-items", classesPanelList.items)}>{contents}</ul>
            </nav>
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
