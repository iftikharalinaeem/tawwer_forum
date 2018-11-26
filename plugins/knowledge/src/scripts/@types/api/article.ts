/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IUserFragment } from "@library/@types/api";
import { Omit } from "@library/@types/utils";

interface IArticleRequiredData {
    knowledgeCategoryID: number | null; //The category the article belongs in.
}

export enum ArticleStatus {
    DELETED = "deleted",
    UNDELETED = "undeleted",
    PUBLISHED = "published",
}

interface IInsertUpdate {
    insertUserID: number;
    updateUserID: number;
    dateInserted: string;
    dateUpdated: string;
}

interface IExpandedInsertUpdate extends IInsertUpdate {
    insertUser?: IUserFragment;
    updateUser?: IUserFragment;
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

export interface IOutlineItem {
    ref: string; // A uniqueID
    level: number; // The heading level.
    text: string; // The text content of the heading.
}

interface IArticleServerManagedData extends IExpandedInsertUpdate {
    articleID: number;
    score: number; // The article score based on helpful reactions.
    countViews: number; // The number of times the article has been viewed.
    url: string; // Full URL to the resource
    outline: IOutlineItem[];
}

// The record
export interface IArticle extends IArticleRequiredData, IArticleDefaultedData, IArticleServerManagedData {}

export interface IArticleFragment {
    articleID: number;
    name: string; //The title of the article
    dateUpdated: string;
    updateUser: IUserFragment;
    url: string; // Full URL to the resource
    excerpt: string; // Excerpt of the article's content.
}

// Request/Response interfaces

// POST /articles
export interface IPostArticleRequestBody extends IArticleRequiredData, Partial<IArticleDefaultedData> {
    draftID?: number;
}
export interface IPostArticleResponseBody extends IArticle {}

// PATCH /articles/:id
export interface IPatchArticleRequestBody extends Partial<IPostArticleRequestBody> {
    articleID: number;
    draftID?: number;
}
export interface IPatchArticleResponseBody extends IArticle {}

// GET /articles/:id
export interface IGetArticleRequestBody {
    articleID: number;
}
export interface IGetArticleResponseBody extends IArticle {}

// PATCH /articles/:id/status
export interface IPatchArticleStatusRequestBody {
    articleID: number;
    status: ArticleStatus;
}
export interface IPatchArticleStatusResponseBody extends IArticle {}

// Drafts
export interface IArticleDraftContents
    extends Partial<IArticleRequiredData>,
        Omit<Partial<IArticleDefaultedData>, "body"> {
    body: any[];
}

export interface IArticleDraft {
    recordID?: number;
    parentRecordID?: number;
    attributes: IArticleDraftContents;
}

export interface IResponseArticleDraft extends IArticleDraft, IInsertUpdate {
    draftID: number;
    recordType: "article";
}

// GET /articles/drafts
export interface IGetArticleDraftsRequest {
    articleID?: number;
    insertUserID?: number;
}
export type IGetArticleDraftsResponse = IResponseArticleDraft[];

// GET /articles/drafts/:id

export interface IGetArticleDraftRequest {
    draftID?: number;
}
export type IGetArticleDraftResponse = IResponseArticleDraft;

// POST /articles/drafts
export type IPostArticleDraftRequest = IArticleDraft;
export type IPostArticleDraftResponse = IResponseArticleDraft;

// PATCH /articles/drafts/:id
export interface IPatchArticleDraftRequest extends Partial<IArticleDraft> {
    draftID: number;
}
export type IPatchArticleDraftResponse = IResponseArticleDraft;

// DELETE /articles/drafts/:id
export interface IDeleteArticleDraftRequest {
    draftID: number;
}
export interface IDeleteArticleDraftResponse {}
