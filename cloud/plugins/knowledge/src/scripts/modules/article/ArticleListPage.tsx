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
import { useKnowledgeBase } from "@knowledge/knowledge-bases/knowledgeBaseHooks";
import Banner from "@vanilla/library/src/scripts/banner/Banner";

export default function ArticleListPage() {
    const siteSection = getSiteSection();
    const query = qs.parse(window.location.search.replace(/^\?/, ""));

    useFallbackBackUrl("/kb");

    const title = query.recommended ? t("Featured Articles") : t("Articles");

    const articles = useArticleList({
        featured: query.recommended ? query.recommended : false,
        knowledgeBaseID: query.knowledgeBaseID ? query.knowledgeBaseID : undefined,
        siteSectionGroup: siteSection.sectionGroup === "vanilla" ? undefined : siteSection.sectionGroup,
        locale: siteSection.contentLocale,
        expand: ["users"],
        page: query.page ? query.page : 1,
        limit: 10,
    });

    let queryString = query.knowledgeBaseID ? `&knowledgBaseID=${query.knowledgeBaseID}` : "";
    queryString = query.recommended ? queryString + `&recommended=${query.recommended}` : queryString + "";
    const knowledgeBase = useKnowledgeBase(query.knowledgeBaseID);

    const hasKB = !!query.knowledgeBaseID;

    if (
        articles.status === LoadStatus.PENDING ||
        articles.status === LoadStatus.LOADING ||
        (hasKB && [LoadStatus.PENDING, LoadStatus.LOADING].includes(knowledgeBase.status))
    ) {
        return <Loader />;
    }

    const articlesError = articles.status === LoadStatus.ERROR && articles.error;
    if (articlesError) {
        return <KbErrorPage defaultError={DefaultKbError.NO_ARTICLES} />;
    }

    if (!articles.data) {
        return <KbErrorPage defaultError={DefaultKbError.NO_ARTICLES} />;
    }

    if (knowledgeBase.error) {
        return <KbErrorPage error={knowledgeBase.error} />;
    }

    const articleResults = articles.data.body.map((article: ISearchResult) => {
        return {
            name: article.name || "",
            meta:
                article.updateUser && article.dateUpdated ? (
                    <ResultMeta updateUser={article.updateUser} dateUpdated={article.dateUpdated} />
                ) : (
                    <></>
                ),
            url: article.url,
            attachments: [],
            location: [],
        };
    });

    return (
        <DocumentTitle title={title}>
            <Banner
                isContentBanner
                backgroundImage={knowledgeBase.data?.bannerImage}
                contentImage={knowledgeBase.data?.bannerContentImage}
            />
            <FeaturedArticleLayout
                results={articleResults}
                pages={articles.data.pagination}
                title={title}
                query={queryString}
            />
        </DocumentTitle>
    );
}
