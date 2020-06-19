/**
 * @author Tuan Nguyen <tuan.nguyen@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import { ICrumb } from "@library/navigation/Breadcrumbs";
import { PublishStatus } from "@library/@types/api/core";
import { IUserFragment } from "@vanilla/library/src/scripts/@types/api/users";

export interface IUnifySearchResponseBody {
    url: string;
    body: string;
    dateInserted: string;
    dateUpdated: string;
    insertUserID: number;
    insertUser: IUserFragment;
    name: string;
    recordID: number;
    recordType: string;
    score?: number;
    type: string;
    updateUserID: number;
    breadcrumbs: ICrumb[];
    status?: PublishStatus;
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
    page?: number;
    limit?: number;
    expandBody?: boolean;
    expand?: string[];
    statues?: PublishStatus[];
}
