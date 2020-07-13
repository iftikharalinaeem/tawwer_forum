/**
 * @author Stéphane (slafleche) LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { useEffect, useState, useCallback } from "react";
import { t } from "@library/utility/appUtils";
import Heading from "@library/layout/Heading";
import classNames from "classnames";
import { IOutlineItem } from "@knowledge/@types/api/article";
import { panelListClasses } from "@library/layout/panelListStyles";
import { articleTOCClasses } from "@knowledge/modules/article/components/articleTOCStyles";
import { useUniqueID } from "@library/utility/idUtils";
import { useLayout } from "@library/layout/LayoutContext";

interface IProps {
    items: IOutlineItem[];
}

/**
 * Implements the table of contents component
 */
export default function ArticleTOC(props: IProps) {
    const MINIMUM_CHILD_COUNT = 1;
    const [rerender, setRerender] = useState(false);
    const { mediaQueries } = useLayout();
    const titleID = useUniqueID("articleTOC");

    const handleHashChange = useCallback(() => {
        setRerender(!rerender);
    }, [setRerender, rerender]);

    useEffect(() => {
        window.addEventListener("hashchange", handleHashChange);
        return () => {
            window.removeEventListener("hashchange", handleHashChange);
        };
    }, [handleHashChange]);

    if (props.items.length < MINIMUM_CHILD_COUNT) {
        return null;
    }
    const classes = articleTOCClasses();
    const classesPanelList = panelListClasses(mediaQueries);

    const contents = props.items
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
                    <a href={href} onClick={handleHashChange} className={classes.link} title={item.text}>
                        {item.text}
                    </a>
                </li>
            );
        });

    return (
        <nav
            key={rerender ? 1 : 0}
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
