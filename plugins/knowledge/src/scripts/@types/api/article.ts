/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IUserFragment } from "@dashboard/@types/api";
import { IArticleRevisionFragment } from "@knowledge/@types/api/articleRevision";

interface IArticleRequiredData {
    knowledgeCategoryID: number | null; //The category the article belongs in.
}

export enum ArticleStatus {
    DELETED = "deleted",
    UNDELETED = "undeleted",
    PUBLISHED = "published",
}

interface IArticleDefaultedData {
    seoName: string; // Displayed in the tag of the page. If empty will be just the name of the article.
    seoDescription: string; // Displayed in the of the page. If empty will be calculated from the article body.
    slug: string; // The path to the article from an import used to support redirects. This is not editable within the UI, but should be accessable via API in case we decide to make it an advanced option.
    sort: number; // The manual sort order of the article.
    status: ArticleStatus;
    body: string; // Content of the article. Defaults to an empty string.
    name: string; // Name of the article. Defaults to an empty string.
    format: string; // Format of the content. Defaults to the site's configured default format.
    locale: string; // Defaults to the current locale.
}

interface IArticleServerManagedData {
    articleID: number;
    insertUserID: number;
    updateUserID: number;
    insertUser?: IUserFragment;
    updateUser?: IUserFragment;
    dateInserted: string;
    dateUpdated: string;
    score: number; // The article score based on helpful reactions.
    countViews: number; // The number of times the article has been viewed.
    url: string; // Full URL to the resource
}

// The record
export interface IArticle extends IArticleRequiredData, IArticleDefaultedData, IArticleServerManagedData {}

// Request/Response interfaces
export interface IPostArticleRequestBody extends IArticleRequiredData, Partial<IArticleDefaultedData> {}

export interface IPatchArticleRequestBody extends Partial<IPostArticleRequestBody> {
    articleID: number;
}

export interface IPostArticleResponseBody extends IArticle {}

export interface IPatchArticleResponseBody extends IArticle {}

export interface IGetArticleResponseBody extends IArticle {}

export interface IPatchArticleStatusRequestBody {
    articleID: number;
    status: ArticleStatus;
}

export interface IPatchArticleStatusResponseBody extends IArticle {}

export interface IArticleFragment {
    articleID: number;
    name: string; //The title of the article
    dateUpdated: string;
    updateUser: IUserFragment;
    url: string; // Full URL to the resource
    excerpt: string; // Excerpt of the article's content.
}
