/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IUserFragment } from "@dashboard/@types/api";
import { IArticleRevisionFragment } from "@knowledge/@types/api/articleRevision";

interface IArticleRequiredData {
    knowledgeCategoryID?: number; //The category the article belongs in.
}

interface IArticleDefaultedData {
    seoName: string; // Displayed in the tag of the page. If empty will be just the name of the article.
    seoDescription: string; // Displayed in the of the page. If empty will be calculated from the article body.
    slug: string; // The path to the article from an import used to support redirects. This is not editable within the UI, but should be accessable via API in case we decide to make it an advanced option.
    sort: number; // The manual sort order of the article.
}

interface IArticleServerManagedData {
    articleID: number;
    articleRevisionID: number;
    articleRevision: IArticleRevisionFragment;
    insertUserID: number;
    updateUserID: number;
    insertUser?: IUserFragment;
    updateUser?: IUserFragment;
    dateInserted: string;
    dateUpdated: string;
    score: number; // The article score based on helpful reactions.
    countViews: number; // The number of times the article has been viewed.
    url: string; // Full URL to the resource
    categoryAncestorIDs?: number[]; // The tree of parent category IDs as a flay array.;
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

export interface IArticleFragment {
    articleID: number;
    name: string; //The title of the article
    updateUser: IUserFragment;
    url: string; // Full URL to the resource
}
