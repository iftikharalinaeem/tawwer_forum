/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { ICrumb } from "@library/navigation/Breadcrumbs";
import { IUserFragment } from "@library/@types/api/users";
import { PublishStatus } from "@library/@types/api/core";
import { string } from "prop-types";
import { DeltaOperation } from "quill/core";

interface IArticleRequiredData {
    knowledgeCategoryID: number | null; //The category the article belongs in.
    locale: string;
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
    status: PublishStatus;
    body: string; // Content of the article. Defaults to an empty string.
    name: string; // Name of the article. Defaults to an empty string.
    format: string; // Format of the content. Defaults to the site's configured default format.
    featured: boolean; // The featured article status
}

export interface IOutlineItem {
    ref: string; // A uniqueID
    level: number; // The heading level.
    text: string; // The text content of the heading.
}

export enum ArticleReactionType {
    HELPFUL = "helpful",
}

export interface IArticleReaction {
    reactionType: ArticleReactionType;
    yes: number;
    no: number;
    total: number;
    userReaction: "yes" | "no" | null;
}

type ArticleTranslationStatus = "out-of-date" | "up-to-date" | "untranslated";

interface IArticleServerManagedData extends IExpandedInsertUpdate {
    articleID: number;
    knowledgeBaseID: number;
    breadcrumbs?: ICrumb[];
    score: number; // The article score based on helpful reactions.
    countViews: number; // The number of times the article has been viewed.
    url: string; // Full URL to the resource
    outline: IOutlineItem[];
    reactions: IArticleReaction[];
    translationStatus: ArticleTranslationStatus;
}

// The record
export interface IArticle extends IArticleRequiredData, IArticleDefaultedData, IArticleServerManagedData {}

export interface IArticleFragment {
    articleID: number;
    name: string; //The title of the article
    dateUpdated: string;
    updateUser: IUserFragment;
    url: string; // Full URL to the resource
    excerpt?: string; // Excerpt of the article's content.
}

export interface IArticleLocale {
    articleRevisionID: number;
    locale: string;
    name: string;
    translationStatus: string;
    url: string;
    lang: string;
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
}
export interface IPatchArticleResponseBody extends IArticle {}

// GET /articles/:id
export interface IGetArticleRequestBody {
    articleID: number;
    locale: string;
}
export interface IGetArticleResponseBody extends IArticle {}

// GET /articles/:id/translations
export interface IGetArticleLocalesRequestBody {
    articleID: number;
}
export type IGetArticleLocalesResponseBody = IArticleLocale[];

// PATCH /articles/:id/status
export interface IPatchArticleStatusRequestBody {
    articleID: number;
    status: PublishStatus;
}
export interface IPatchArticleStatusResponseBody extends IArticle {}

// Drafts
export interface IArticleDraftAttrs
    extends Partial<IArticleRequiredData>,
        Omit<Partial<IArticleDefaultedData>, "body"> {
    discussionID?: number;
}

export interface IArticleDraft {
    recordID?: number;
    parentRecordID?: number;
    attributes: IArticleDraftAttrs;
    body: string;
    format: string;
}

export interface IResponseArticleDraft extends IArticleDraft, IInsertUpdate {
    draftID: number;
    recordType: "article";
    excerpt: string;
    insertUser?: IUserFragment;
    updateUser?: IUserFragment;
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

// GET /articles/from-discussion
export interface IGetArticleFromDiscussionRequest {
    discussionID: number;
}

export interface ICommunityPost {
    body: string | DeltaOperation[];
    format: string;
    name: string;
    url: string;
}

export interface IGetArticleFromDiscussionResponse extends ICommunityPost {
    acceptedAnswers?: ICommunityPost[];
}

export interface IArticleTranslation {
    articleRevisionID: number;
    name: string;
    url: string;
    locale: string;
    sourceLocale: string;
    translationStatus: ArticleTranslationStatus;
}

export interface IRelatedArticle {
    name: string;
    body: string;
    url: string;
    insertUserID: number;
    updateUserID: number;
    dateInserted: string;
    dateUpdated: string;
    knowledgeCategoryID: number;
    status: PublishStatus;
    recordID: number;
    recordType: string;
}

export interface IFeatureArticle {
    articleID: number;
    featured: boolean;
}
