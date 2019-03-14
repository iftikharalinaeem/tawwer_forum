/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { ICrumb } from "@library/navigation/Breadcrumbs";
import { ArticleStatus } from "@knowledge/@types/api/article";
import { IUserFragment } from "@library/@types/api/users";

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
    breadcrumbs: ICrumb[];
}

export interface ISearchRequestBody {
    body?: string;
    name?: string;
    all?: string;
    dateUpdated?: string;
    global?: boolean;
    // Filter by date when the article was updated.
    // This filter receive a string that can take two forms.
    // A single date that matches '{Operator}{DateTime}' where {Operator} can be =, <, >, <=, >= and, if omitted, defaults to =.
    // A date range that matches '{Opening}{DateTime},{DateTime}{Closing}' where {Opening} can be '[' or '(' and {Closing} can be ']' or ')'. '[]' are inclusive and '()' are exclusive.
    insertUserIDs?: number[];
    updateUserIDs?: number[];
    knowledgeBaseID?: number;
    statuses?: ArticleStatus[];
    expand?: string[];
    page?: number;
    limit?: number;
}

export type ISearchResponseBody = ISearchResult[];
