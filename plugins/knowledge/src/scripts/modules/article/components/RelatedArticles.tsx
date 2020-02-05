/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import * as React from "react";
import SmartLink from "@library/routing/links/SmartLink";
import { IRelatedArticle } from "@knowledge/@types/api/article";

interface IProps {
    articles: IRelatedArticle[];
}

/**
 * Implements the related articles component
 */
export default function RelatedArticles(props: IProps) {
    const { articles } = props;

    return (
        <>
            <h3>Related Articles</h3>
            <ul>
                {articles
                    .filter((article, index) => index <= 4)
                    .map(article => {
                        return (
                            <li key={article.recordID}>
                                <SmartLink to={article.url}>{article.name}</SmartLink>
                            </li>
                        );
                    })}
            </ul>
        </>
    );
}
