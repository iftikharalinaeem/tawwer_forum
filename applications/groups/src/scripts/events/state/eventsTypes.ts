/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IUserFragment } from "@vanilla/library/src/scripts/@types/api/users";
import { IWithPagination, ILinkPages } from "@vanilla/library/src/scripts/navigation/SimplePagerModel";
import { ICrumb } from "@vanilla/library/src/scripts/navigation/Breadcrumbs";

export interface IEvent {
    eventID: number;
    name: string;
    body: string;
    format: string;
    parentRecordType: string;
    parentRecordID: number;
    dateStarts: string;
    dateEnds: string;
    allDayEvent: boolean;
    location: string;
    dateInserted: string;
    dateUpdated?: string;
    attending: string;
    insertUser: IUserFragment;
    updatedUser: IUserFragment;
    groupID?: number;
    url: string;
}

export interface IEventList {
    events: IEvent[];
    pagination: ILinkPages;
}

export interface IEventParentRecord {
    name: string;
    description: string;
    parentRecordID: number;
    parentRecordType: string;
    url: string;
    breadcrumbs: ICrumb[];
    imageUrl: string;
}
