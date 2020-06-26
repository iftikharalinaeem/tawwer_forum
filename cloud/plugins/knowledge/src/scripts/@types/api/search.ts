/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { ICrumb } from "@library/navigation/Breadcrumbs";
import { IUserFragment } from "@library/@types/api/users";
import { PublishStatus } from "@library/@types/api/core";

export interface ISearchResult {
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
    knowledgeCategoryIDs?: number[]; // should be converted to categoryID's if using /knowledge/search
    knowledgeCategoryID?: number; // should be converted to categoryID's if using /knowledge/search
    statuses?: PublishStatus[];
    expand?: string[];
    page?: number;
    limit?: number;
    categoryIDs?: number[];
    locale?: string;
    siteSectionGroup?: string;
    featured?: boolean;
    "only-translated"?: boolean;
}

export type ISearchResponseBody = ISearchResult[];
