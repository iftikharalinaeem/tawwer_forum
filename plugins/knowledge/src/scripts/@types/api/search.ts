/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import { ArticleStatus } from "@knowledge/@types/api";

export interface ISearchResult {
    name: string;
    bodyPlainText: string;
    bodyRendered: string;
    url: string;
    recordID: string;
    recordType: string;
}

export interface ISearchRequestBody {
    query?: string;
    title?: string;
    dateInserted?: string;
    // A single date that matches '{Operator}{DateTime}' where {Operator} can be =, <, >, <=, >= and, if omitted, defaults to =.
    // A date range that matches '{Opening}{DateTime},{DateTime}{Closing}' where {Opening} can be '[' or '(' and {Closing} can be ']' or ')'. '[]' are inclusive and '()' are exclusive.
    authorIDs?: number[];
    knowledgeBaseID?: number;
    allowedStatuses?: ArticleStatus[];
}

export type ISearchResponseBody = ISearchResult[];
