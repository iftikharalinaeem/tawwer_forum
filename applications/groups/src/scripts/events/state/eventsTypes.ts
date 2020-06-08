/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IUserFragment } from "@vanilla/library/src/scripts/@types/api/users";
import { IWithPagination, ILinkPages } from "@vanilla/library/src/scripts/navigation/SimplePagerModel";
import { ICrumb } from "@vanilla/library/src/scripts/navigation/Breadcrumbs";

export enum EventPermissionName {
    ORGANIZER = "Organizer",
    CREATE = "Create",
    EDIT = "Edit",
    MEMBER = "Member",
    VIEW = "View",
    ATTEND = "Attend",
}

export enum EventAttendance {
    RSVP = "rsvp", // only for default value in EventAttendanceDropDown
    GOING = "yes",
    MAYBE = "maybe",
    NOT_GOING = "no",
}

export interface IEvent {
    eventID: number;
    name: string;
    body: string;
    excerpt: string;
    format: string;
    parentRecordType: string;
    parentRecordID: number;
    dateStarts: string;
    dateEnds: string;
    allDayEvent: boolean;
    location: string;
    dateInserted: string;
    dateUpdated?: string;
    attending: EventAttendance | null;
    insertUser: IUserFragment;
    updatedUser: IUserFragment;
    groupID?: number;
    url: string;
    parentRecord?: IParentRecordFragment;
    permissions?: Record<EventPermissionName, boolean>;
    breadcrumbs: ICrumb[];
}

export interface IEventList {
    events: IEvent[];
    pagination: ILinkPages;
}

interface IParentRecordFragment {
    name: string;
    recordID: number;
    recordType: string;
    url: string;
}

export interface IEventParentRecord {
    name: string;
    description: string;
    parentRecordID: number;
    parentRecordType: string;
    url: string;
    breadcrumbs: ICrumb[];
    bannerUrl: string | null;
    iconUrl: string | null;
}

export interface IEventParticipant {
    attending: EventAttendance;
    dateInserted: string;
    eventID: number;
    user: IUserFragment;
    userID: number;
}

export interface IEventParticipantList {
    eventID: number;
    pagination: ILinkPages;
    participants: IEventParticipant[];
}

export interface IEventParticipantsByAttendance {
    eventID: number;
    attending: EventAttendance;
    pagination: ILinkPages;
    participants: IEventParticipant[];
}

export interface IEventWithParticipants {
    event: IEvent;
    participants: IEventParticipant[];
}
