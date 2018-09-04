import { IUserFragment } from "@dashboard/@types/api";

/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
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

interface IArticleRequiredData {
    name: string; // The title of the article
    locale: string; // The locale the article was written in
    body: string; // The content of the article.
    format: Format; // The format of the article.
    knowledgeCategoryID: number; //The category the article belongs in.
}

interface IArticleDefaultedData {
    seoName: string; // Displayed in the tag of the page. If empty will be just the name of the article.
    seoDescription: string; // Displayed in the of the page. If empty will be calculated from the article body.
    slug: string; // The path to the article from an import used to support redirects. This is not editable within the UI, but should be accessable via API in case we decide to make it an advanced option.
    sort: number; // The manual sort order of the article.
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
    bodyRendered: string;
    categoryAncestorIDs?: number[]; // The tree of parent category IDs as a flay array.;
}

type ArticleExpandFields = "user" | "ancestors";

// The record
export interface IArticle extends IArticleRequiredData, IArticleDefaultedData, IArticleServerManagedData {}

// Request/Response interfaces
export interface IPostArticleRequestBody extends IArticleRequiredData, Partial<IArticleDefaultedData> {}

export interface IPostArticleResponseBody extends IArticle {}

export interface IGetArticleRequestBody {
    id: number;
    expand?: ArticleExpandFields[];
}

export interface IGetArticleResponseBody extends IArticle {}

export interface IArticleFragment {
    name: string; //The title of the article
    updateUser: IUserFragment;
    url: string; // Full URL to the resource
}
