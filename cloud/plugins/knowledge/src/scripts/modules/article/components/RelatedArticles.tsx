/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import * as React from "react";
import SmartLink from "@library/routing/links/SmartLink";
import { IRelatedArticle } from "@knowledge/@types/api/article";
import { globalVariables } from "@library/styles/globalStyleVars";
import { unit } from "@library/styles/styleHelpers";
import { relatedArticlesClasses } from "@knowledge/modules/article/components/relatedArticlesStyles";
import Heading from "@library/layout/Heading";
import { panelListClasses } from "@library/layout/panelListStyles";
import classNames from "classnames";
import { useLayout } from "@library/layout/LayoutContext";

interface IProps {
    articles: IRelatedArticle[];
}

/**
 * Implements the related articles component
 */
export default function RelatedArticles(props: IProps) {
    const { articles } = props;
    const classes = relatedArticlesClasses();
    const panelClasses = panelListClasses(useLayout().mediaQueries);
    const content =
        articles.length === 0 ? (
            <></>
        ) : (
            <>
                <hr className={classNames(classes.border, panelClasses.title)} />
                <Heading depth={3} title={"Related Articles"} className={classes.header} />
                <ul className={classes.linkList}>
                    {articles.map(article => {
                        return (
                            <li key={article.recordID} className={classes.linkItem}>
                                <SmartLink to={article.url} className={classes.link}>
                                    {article.name}
                                </SmartLink>
                            </li>
                        );
                    })}
                </ul>
            </>
        );
    return content;
}
