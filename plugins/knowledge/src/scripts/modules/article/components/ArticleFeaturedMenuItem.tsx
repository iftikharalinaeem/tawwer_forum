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
import { t } from "@vanilla/i18n/src";

export interface IRecommendArticleProps {
    article: IArticle;
}

/**
 * Implements featuring/un-featuring and article.
 */
export default function ArticleFeaturedMenuItem(props: IRecommendArticleProps) {
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
            label={t("Feature Article")}
            status={status}
            isLoading={featured.status === LoadStatus.LOADING}
        />
    );
}
