/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IUserFragment } from "@dashboard/@types/api";

export enum Format {
    TEXT = "text",
    TEXTEX = "textex",
    MARKDOWN = "markdown",
    WYSIWYG = "wysiwyg",
    HTML = "html",
    BBCODE = "bbcode",
    RICH = "rich"
}

export interface IArticleRevisionFragment {
    articleRevisionID: number;
    articleID: number;
    status: string;
    name: string;
    format: Format;
    bodyRendered: string;
    locale: string;
    insertUser: IUserFragment;
    dateInserted: string;
}

interface IArticleRevisionRequiredData {
    articleID: number;
    name: string; // The title of the article
    body: string; // The content of the article.
    format: Format; // The format of the article.
}

interface IArticleRevisionDefaultedData {
    locale: string; // The locale the article was written in
    status: string; // Revision status (e.g. "published")
}

interface IArticleRevisionServerManagedData {
    articleRevisionID: number;
    insertUserID: number;
    insertUser?: IUserFragment;
    dateInserted: string;
    bodyRendered: string;
}

// The record
export interface IArticleRevision
    extends IArticleRevisionRequiredData,
        IArticleRevisionDefaultedData,
        IArticleRevisionServerManagedData {}

// Request/Response interfaces
export interface IPostArticleRevisionRequestBody
    extends IArticleRevisionRequiredData,
        Partial<IArticleRevisionDefaultedData> {}

export interface IPostArticleRevisionResponseBody extends IArticleRevision {}

export interface IGetArticleRevisionRequestBody {
    id: number;
}

export interface IGetArticleRevisionResponseBody extends IArticleRevision {}
