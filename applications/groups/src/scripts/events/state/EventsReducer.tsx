import produce from "immer";
import { IDateTime } from "@library/content/DateTime";
import { IUserFragment } from "@library/@types/api/users";
import { reducerWithInitialState } from "typescript-fsa-reducers";
import { ILoadable } from "@library/@types/api/core";

export interface IEvent {
    eventID: number;
    name: string;
    excerpt: string;
    format: string;
    parentRecordType: string; //enum
    parentRecordID: number;
    dateStarts: string;
    dateEnds: string;
    allDayEvent: boolean;
    location: string;
    dateInserted: string;
    dateUpdated?: string;
    user: IUserFragment;
    updatedUser: IUserFragment;
}

export interface IUserParticipationStatus {}

export interface IEventState {
    events: ILoadable<{
        event: IEvent;
    }>;
}

export const eventsReducer = produce(reducerWithInitialState<>());
