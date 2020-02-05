/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import * as React from "react";

import { ISearchResponseBody, ISearchResult } from "@knowledge/@types/api/search";
import SmartLink from "@library/routing/links/SmartLink";

export interface ICard {
    image?: string;
    title: string;
    description: string;
    url: string;
}

interface IProps {
    articles: ISearchResult[];
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
                {articles.map(article => {
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
