/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import { ArticleStatus } from "@knowledge/@types/api";
import { IUserFragment } from "@library/@types/api";
import { ICrumb } from "@library/components/Breadcrumbs";

export interface ISearchResult {
    name: string;
    body: string;
    url: string;
    insertUserID: number;
    updateUserID: number;
    dateInserted: string;
    dateUpdated: string;
    knowledgeCategoryID: number;
    status: ArticleStatus;
    recordID: number;
    recordType: string;
    updateUser?: IUserFragment;
    knowledgeCategory?: {
        knowledgeCategoryID: number;
        breadcrumbs: ICrumb[];
    };
}

export interface ISearchRequestBody {
    body?: string;
    name?: string;
    dateUpdated?: string;
    // Filter by date when the article was updated.
    // This filter receive a string that can take two forms.
    // A single date that matches '{Operator}{DateTime}' where {Operator} can be =, <, >, <=, >= and, if omitted, defaults to =.
    // A date range that matches '{Opening}{DateTime},{DateTime}{Closing}' where {Opening} can be '[' or '(' and {Closing} can be ']' or ')'. '[]' are inclusive and '()' are exclusive.
    insertUserIDs?: number[];
    updateUserIDs?: number[];
    knowledgeBaseID?: number;
    statuses?: ArticleStatus[];
    expand?: string[];
}

export type ISearchResponseBody = ISearchResult[];
