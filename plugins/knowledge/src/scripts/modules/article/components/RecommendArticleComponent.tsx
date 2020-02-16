/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import * as React from "react";
import { useState } from "react";

import { IArticle } from "@knowledge/@types/api/article";
import { useArticleActions } from "@knowledge/modules/article/ArticleActions";
import { useArticleMenuState } from "@knowledge/modules/article/ArticleMenuModel";
import { LoadStatus } from "@library/@types/api/core";
import DropDownSwitchButton from "@library/flyouts/DropDownSwitchButton";

export interface IRecommendArticleProps {
    article: IArticle;
}

/**
 *
 */
export default function RecommendArticle(props: IRecommendArticleProps) {
    const { putFeaturedArticles } = useArticleActions();
    const { featured } = useArticleMenuState();
    const [status, setStatus] = useState(props.article.featured);

    const featureArticle = () => {
        putFeaturedArticles({ articleID: props.article.articleID, featured: !props.article.featured });
        setStatus(!props.article.featured);
    };

    return (
        <DropDownSwitchButton
            onClick={featureArticle}
            label={"Recommend Articles"}
            status={status}
            isLoading={featured.status === LoadStatus.LOADING}
        />
    );
}
