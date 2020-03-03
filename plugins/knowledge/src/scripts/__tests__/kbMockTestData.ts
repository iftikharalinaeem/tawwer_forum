/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import {
    Format,
    IGetArticleRevisionsRequestBody,
    IGetArticleRevisionsResponseBody,
    IRevisionFragment,
    IRevision,
} from "@knowledge/@types/api/articleRevision";
import {
    IResponseArticleDraft,
    IArticle,
    IGetArticleFromDiscussionResponse,
    IGetArticleResponseBody,
} from "@knowledge/@types/api/article";
import { PublishStatus } from "@library/@types/api/core";
import {
    IKnowledgeBase,
    KnowledgeBaseStatus,
    KbViewType,
    KnowledgeBaseSortMode,
    ISiteSection,
} from "@knowledge/knowledge-bases/KnowledgeBaseModel";

export function dummyDraft(overrides: Partial<IArticle> = {}): IResponseArticleDraft {
    return {
        draftID: 1,
        recordType: "article",
        recordID: 2,
        parentRecordID: 1,
        excerpt: "Test excerpt draft",
        insertUserID: 1,
        updateUserID: 1,
        dateInserted: "",
        dateUpdated: "",
        body: `[{"insert": "Hello Draft."}]`,
        format: Format.RICH,
        attributes: {
            name: "test",
            knowledgeCategoryID: 1,
        },
        ...overrides,
    };
}

export function dummyArticle(overrides: Partial<IArticle> = {}): IArticle {
    return {
        articleID: 1,
        knowledgeCategoryID: 4,
        knowledgeBaseID: 1,
        sort: 0,
        seoName: "",
        seoDescription: "",
        slug: "example-article",
        status: PublishStatus.PUBLISHED,
        score: 5,
        countViews: 5,
        outline: [],
        insertUserID: 0,
        updateUserID: 0,
        dateInserted: "",
        dateUpdated: "",
        name: "Example Article",
        body: "<p />",
        format: "rich",
        locale: "en",
        url: "/kb/articles/1-example-article",
        reactions: [],
        translationStatus: "up-to-date",
        featured: false,
        ...overrides,
    };
}

export function dummyDiscussionArticle(
    overrides: Partial<IGetArticleFromDiscussionResponse> = {},
): IGetArticleFromDiscussionResponse {
    return {
        name: "Hello World",
        body: [
            {
                insert: "I am a discussion.\n",
            },
        ],
        format: "Rich",
        url: "/discussion/1/hello-world",
        ...overrides,
    };
}
export function dummyEditArticle(overrides: Partial<IGetArticleResponseBody> = {}): IGetArticleResponseBody {
    return { ...dummyArticle({ body: '[{"insert":"Hello Article."}]' }), ...overrides };
}

export function dummyArticleTranslations() {
    return [
        {
            articleRevisionID: 5,
            name: "Test French article again",
            url: "http://dev.vanilla.localhost/statistiques/kb/articles/105-test-french-article-again",
            locale: "fr",
            sourceLocale: "en",
            translationStatus: "out-of-date",
        },
        {
            articleRevisionID: 4,
            name: "Test English article",
            url: "http://dev.vanilla.localhost/analytics/kb/articles/105-test-english-article",
            locale: "en",
            sourceLocale: "en",
            translationStatus: "out-of-date",
        },
    ];
}

export function dummyRevision(overrides: Partial<IRevision> = {}): IRevision {
    return {
        articleRevisionID: 6,
        articleID: 1,
        name: "Example Revision",
        body: '[{"insert":"Hello Revision"}]',
        format: Format.RICH,
        locale: "en",
        status: "published",
        insertUserID: 1,
        bodyRendered: "",
        dateInserted: "",
    };
}

export function dummyKnowledgeBase(overrides: Partial<IKnowledgeBase> = {}): IKnowledgeBase {
    return {
        knowledgeBaseID: 1,
        name: "test",
        description: "test",
        sortArticles: KnowledgeBaseSortMode.MANUAL,
        insertUserID: 1,
        dateInserted: "",
        updateUserID: 1,
        dateUpdated: "",
        countArticles: 100,
        countCategories: 100,
        urlCode: "/test",
        url: "http://test.com/kb/test",
        icon: "",
        status: KnowledgeBaseStatus.PUBLISHED,
        bannerImage: "",
        sourceLocale: "en",
        viewType: KbViewType.GUIDE,
        rootCategoryID: 1,
        defaultArticleID: null,
        siteSectionGroup: "vanilla",
        siteSections: [dummySiteSection()],
        isUniversalSource: false,
        universalSources: [],
        universalTargetIDs: [],
        hasCustomPermissions: false,
        ...overrides,
    };
}

export function dummySiteSection(overrides: Partial<ISiteSection> = {}): ISiteSection {
    return {
        sectionID: "test",
        siteSectionGroup: "test",
        basePath: "/test",
        name: "test",
        contentLocale: "en",
        ...overrides,
    };
}
