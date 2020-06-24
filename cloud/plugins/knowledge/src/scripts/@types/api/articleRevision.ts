import { IUserFragment } from "@library/@types/api/users";

/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

export enum Format {
    TEXT = "text",
    TEXTEX = "textex",
    MARKDOWN = "markdown",
    WYSIWYG = "wysiwyg",
    HTML = "html",
    BBCODE = "bbcode",
    RICH = "rich",
}

export interface IRevisionFragment {
    articleRevisionID: number;
    articleID: number;
    status: "published" | null;
    name: string;
    locale: string;
    insertUser: IUserFragment;
    dateInserted: string;
}

interface IRevisionRequiredData {
    articleID: number;
    name: string; // The title of the article
    body: string; // The content of the article.
    format: Format; // The format of the article.
}

interface IRevisionDefaultedData {
    locale: string; // The locale the article was written in
    status: string; // Revision status (e.g. "published")
}

interface IRevisionServerManagedData {
    articleRevisionID: number;
    insertUserID: number;
    insertUser?: IUserFragment;
    bodyRendered: string;
    dateInserted: string;
}

// The record
export interface IRevision extends IRevisionRequiredData, IRevisionDefaultedData, IRevisionServerManagedData {}

export interface IGetArticleRevisionsRequestBody {
    articleID: number;
    page?: number;
    limit?: number;
    locale?: string;
}

export type IGetArticleRevisionsResponseBody = IRevisionFragment[];

export interface IGetRevisionRequestBody {
    revisionID: number;
}

export interface IGetRevisionResponseBody extends IRevision {}
