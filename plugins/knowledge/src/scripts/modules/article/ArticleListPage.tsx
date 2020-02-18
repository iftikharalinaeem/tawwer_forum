/**
 * @author Chris Chabilall <chris.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import DocumentTitle from "@library/routing/DocumentTitle";
import { useArticleList } from "@knowledge/modules/article/ArticleModel";
import qs from "qs";
import { LoadStatus } from "@library/@types/api/core";
import { ResultMeta } from "@library/result/ResultMeta";
import { ISearchResult } from "@knowledge/@types/api/search";
import Loader from "@library/loaders/Loader";
import { KbErrorPage } from "@knowledge/pages/KbErrorPage";
import { getSiteSection } from "@library/utility/appUtils";
import FeaturedArticleLayout from "@knowledge/modules/article/components/FeaturedArticlesLayout";
import { useFallbackBackUrl } from "@library/routing/links/BackRoutingProvider";
import { t } from "@vanilla/i18n/src";
import { DefaultKbError } from "@knowledge/modules/common/KbErrorMessages";

export default function ArticleListPage() {
    const siteSection = getSiteSection();
    const query = qs.parse(window.location.search.replace(/^\?/, ""));

    useFallbackBackUrl("/kb");

    let queryParam = query.knowledgeBaseID ? "knowledgeBaseID" : "siteSectionGroup";
    let queryValue = queryParam === "knowledgeBaseID" ? query.knowledgeBaseID : siteSection.sectionGroup;

    const articles = useArticleList({
        featured: query.recommended ? query.recommended : true,
        [queryParam]: queryValue,
        locale: siteSection.contentLocale,
        expand: ["user"],
        page: query.page ? query.page : 1,
        limit: 10,
    });

    if (articles.status === LoadStatus.PENDING || articles.status === LoadStatus.LOADING) {
        return <Loader />;
    }

    const articlesError = articles.status === LoadStatus.ERROR && articles.error;
    if (articlesError) {
        return <KbErrorPage apiError={articles.error} />;
    }

    if (!articles.data) {
        return <KbErrorPage defaultError={DefaultKbError.GENERIC} />;
    }

    const articleResults = articles.data.body.map((article: ISearchResult) => {
        return {
            name: article.name || "",
            meta: <ResultMeta updateUser={article.updateUser} dateUpdated={article.dateUpdated} />,
            url: article.url,
            attachments: [],
            location: [],
        };
    });

    return (
        <DocumentTitle title={t("Featured Articles")}>
            <FeaturedArticleLayout results={articleResults} pages={articles.data.pagination} />
        </DocumentTitle>
    );
}
