/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import * as React from "react";
import ButtonSwitch from "@library/forms/ButtonSwitch";
import { IArticle } from "@knowledge/@types/api/article";
import { useArticleActions } from "@knowledge/modules/article/ArticleActions";
import { useState } from "react";

export interface IRecommendArticleProps {
    article: IArticle;
    isLoading: boolean;
}

/**
 *
 */
export default function RecommendArticle(props: IRecommendArticleProps) {
    const { putFeaturedArticles } = useArticleActions();
    const [status, setStatus] = useState(props.article.featured);

    const featureArticle = () => {
        putFeaturedArticles({ articleID: props.article.articleID, featured: !props.article.featured });
        setStatus(!props.article.featured);
    };

    return (
        <ButtonSwitch
            onClick={featureArticle}
            label={"Recommend Articles"}
            status={status}
            isLoading={props.isLoading}
        />
    );
}
