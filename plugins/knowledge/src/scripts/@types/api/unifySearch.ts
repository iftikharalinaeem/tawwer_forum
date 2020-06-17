/**
 * @author Tuan Nguyen <tuan.nguyen@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import { ICrumb } from "@library/navigation/Breadcrumbs";

export interface IUnifySearchResponseBody {
    url: string;
    body: string;
    categoryID: number;
    commentID?: number;
    dateInserted?: string;
    dateUpdated?: string;
    discussionID: number;
    groupID?: number;
    insertUserID: number;
    name: string;
    recordID: number;
    recordType: string;
    score: number;
    type: string;
    updateUserID: number;
    breadcrumbs?: ICrumb[];
}

export interface IUnifySearchRequestBody {
    query?: string;
    recordTypes?: string[];
    types?: string[];
    discussionID?: number;
    categoryID?: number;
    followedCategories?: boolean;
    includeChildCategories?: boolean;
    includeArchivedCategories?: boolean;
    name?: string;
    insertUserNames?: string[];
    insertUserIDs?: number[];
    dateInserted?: string;
    tags?: string[];
    tagOperator?: string[];
    page: number;
    limit?: number;
    expandBody?: boolean;
    expand?: string[];
}
