/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import { EventsActions } from "@groups/events/state/EventsActions";
import {
    IEventList,
    IEventParentRecord,
    IEventWithParticipants,
    EventAttendance,
    IEventParticipantList,
    IEventParticipantsByAttendance,
} from "@groups/events/state/eventsTypes";
import { ILoadable, LoadStatus } from "@library/@types/api/core";
import { ICoreStoreState } from "@library/redux/reducerRegistry";
import { stableObjectHash } from "@vanilla/utils";
import produce from "immer";
import { reducerWithInitialState } from "typescript-fsa-reducers";

export interface IEventsState {
    eventsLists: Record<string, ILoadable<IEventList>>;
    eventParentRecords: Record<string, ILoadable<IEventParentRecord>>;
    eventsByID: Record<number, ILoadable<IEventWithParticipants>>;
    partipateStatusByEventID: Record<number, ILoadable<{ attending: EventAttendance }>>;
    deleteStatusesByID: Record<number, ILoadable<{}>>;
    participantsByEventID: Record<number, ILoadable<IEventParticipantList>>;
    participantsByAttendanceByEventID: Record<string, ILoadable<IEventParticipantsByAttendance>>;
}

export interface IEventsStoreState extends ICoreStoreState {
    events: IEventsState;
}

const DEFAULT_EVENT_STATE: IEventsState = {
    eventsLists: {},
    eventParentRecords: {},
    eventsByID: {},
    partipateStatusByEventID: {},
    deleteStatusesByID: {},
    participantsByEventID: {},
    participantsByAttendanceByEventID: {},
};

export const eventsReducer = produce(
    reducerWithInitialState<IEventsState>(DEFAULT_EVENT_STATE)
        .case(EventsActions.getEventListACs.started, (nextState, payload) => {
            const hash = stableObjectHash(payload);
            nextState.eventsLists[hash] = {
                status: LoadStatus.LOADING,
            };
            return nextState;
        })
        .case(EventsActions.getEventListACs.done, (nextState, payload) => {
            const hash = stableObjectHash(payload.params);
            nextState.eventsLists[hash] = {
                status: LoadStatus.SUCCESS,
                data: payload.result,
            };

            return nextState;
        })
        .case(EventsActions.getEventListACs.failed, (nextState, payload) => {
            const hash = stableObjectHash(payload.params);
            nextState.eventsLists[hash].status = LoadStatus.ERROR;
            nextState.eventsLists[hash].error = payload.error;

            return nextState;
        })
        .case(EventsActions.getEventParentRecord.started, (nextState, params) => {
            const hash = params.parentRecordType + params.parentRecordID;
            nextState.eventParentRecords[hash] = {
                status: LoadStatus.LOADING,
            };
            return nextState;
        })
        .case(EventsActions.getEventParentRecord.done, (nextState, payload) => {
            const { params } = payload;
            const hash = params.parentRecordType + params.parentRecordID;
            nextState.eventParentRecords[hash] = {
                status: LoadStatus.SUCCESS,
                data: payload.result,
            };

            return nextState;
        })
        .case(EventsActions.getEventParentRecord.failed, (nextState, payload) => {
            const { params } = payload;
            const hash = params.parentRecordType + params.parentRecordID;
            nextState.eventParentRecords[hash].status = LoadStatus.ERROR;
            nextState.eventParentRecords[hash].error = payload.error;

            return nextState;
        })
        .case(EventsActions.getEventParticipantsACs.started, (nextState, params) => {
            const existing = nextState.participantsByEventID[params.eventID];

            if (existing) {
                existing.status = LoadStatus.LOADING;
            } else {
                nextState.participantsByEventID[params.eventID] = {
                    status: LoadStatus.LOADING,
                };
            }
            return nextState;
        })
        .case(EventsActions.getEventParticipantsACs.done, (nextState, payload) => {
            const { eventID } = payload.params;
            const data = payload.result;

            const existing = nextState.participantsByEventID[eventID];
            if (existing && existing.data) {
                existing.data.participants = existing.data.participants.concat(data.participants);
                // We understand that only next in pagination matters, all items up to next
                // have been accumulated
                existing.data.pagination = data.pagination;
                existing.status = LoadStatus.SUCCESS;
            } else {
                nextState.participantsByEventID[eventID] = {
                    status: LoadStatus.SUCCESS,
                    data,
                };
            }

            return nextState;
        })
        .case(EventsActions.getEventParticipantsACs.failed, (nextState, payload) => {
            const { eventID } = payload.params;
            const { error } = payload;
            nextState.participantsByEventID[eventID] = {
                status: LoadStatus.SUCCESS,
                error,
            };
            return nextState;
        })
        .case(EventsActions.getEventParticipantsByAttendanceACs.started, (nextState, params) => {
            const { eventID, attending } = params;
            const hash = stableObjectHash({ eventID, attending });
            const existing = nextState.participantsByAttendanceByEventID[hash];

            if (existing) {
                existing.status = LoadStatus.LOADING;
            } else {
                nextState.participantsByAttendanceByEventID[hash] = { status: LoadStatus.LOADING };
            }
            return nextState;
        })
        .case(EventsActions.getEventParticipantsByAttendanceACs.done, (nextState, payload) => {
            const { eventID, attending } = payload.params;
            const hash = stableObjectHash({ eventID, attending });
            const data: IEventParticipantsByAttendance = payload.result;

            const existing = nextState.participantsByAttendanceByEventID[hash];
            if (existing && existing.data) {
                existing.data.participants = existing.data.participants.concat(data.participants);
                // We understand that only next in pagination matters, all items up to next
                // have been accumulated
                existing.data.pagination = data.pagination;
                existing.status = LoadStatus.SUCCESS;
            } else {
                nextState.participantsByAttendanceByEventID[hash] = {
                    status: LoadStatus.SUCCESS,
                    data,
                };
            }

            return nextState;
        })
        .case(EventsActions.getEventParticipantsByAttendanceACs.failed, (nextState, payload) => {
            const { eventID, attending } = payload.params;
            const hash = stableObjectHash({ eventID, attending });
            const { error } = payload;
            nextState.participantsByAttendanceByEventID[hash] = {
                status: LoadStatus.SUCCESS,
                error,
            };
            return nextState;
        })
        .case(EventsActions.getEventACs.started, (nextState, params) => {
            nextState.eventsByID[params.eventID] = {
                status: LoadStatus.LOADING,
            };
            return nextState;
        })
        .case(EventsActions.getEventACs.done, (nextState, payload) => {
            const { eventID } = payload.params;
            const data = payload.result;

            nextState.eventsByID[eventID] = {
                status: LoadStatus.SUCCESS,
                data,
            };

            return nextState;
        })
        .case(EventsActions.getEventACs.failed, (nextState, payload) => {
            const { eventID } = payload.params;
            const { error } = payload;
            nextState.eventsByID[eventID] = {
                status: LoadStatus.SUCCESS,
                error,
            };
            return nextState;
        })
        .case(EventsActions.postEventParticipants_ACS.started, (nextState, params) => {
            nextState.partipateStatusByEventID[params.eventID] = {
                status: LoadStatus.LOADING,
                data: {
                    attending: params.attending,
                },
            };

            return nextState;
        })
        .case(EventsActions.postEventParticipants_ACS.done, (nextState, payload) => {
            nextState.partipateStatusByEventID[payload.params.eventID] = {
                status: LoadStatus.SUCCESS,
                data: {
                    attending: payload.result.attending,
                },
            };

            // Attempt to update an already fetch event participation status.
            const existingEvent = nextState.eventsByID[payload.result.eventID];
            if (existingEvent && existingEvent.data) {
                const modifiedUserID = payload.result.userID;
                const modifiedAttendingStatus = payload.result.attending;

                // Modify the participants.
                const { event, participants } = existingEvent.data;
                event.attending = modifiedAttendingStatus;

                let wasAdded = false;
                for (const participant of participants) {
                    if (participant.userID === modifiedUserID) {
                        participant.attending = modifiedAttendingStatus;
                        wasAdded = true;
                        break;
                    }
                }

                if (!wasAdded) {
                    participants.push(payload.result);
                }
            }

            return nextState;
        })
        .case(EventsActions.postEventParticipants_ACS.failed, (nextState, payload) => {
            nextState.partipateStatusByEventID[payload.params.eventID] = {
                status: LoadStatus.ERROR,
                error: payload.error,
            };

            return nextState;
        })
        .case(EventsActions.deleteEvent_ACS.started, (nextState, params) => {
            nextState.deleteStatusesByID[params.eventID] = {
                status: LoadStatus.LOADING,
            };
            return nextState;
        })
        .case(EventsActions.deleteEvent_ACS.done, (nextState, payload) => {
            nextState.deleteStatusesByID[payload.params.eventID] = {
                status: LoadStatus.SUCCESS,
            };
            return nextState;
        })
        .case(EventsActions.deleteEvent_ACS.failed, (nextState, payload) => {
            nextState.deleteStatusesByID[payload.params.eventID] = {
                status: LoadStatus.ERROR,
                error: payload.error,
            };
            return nextState;
        })
        .case(EventsActions.clearDeleteStatusAC, (nextState, payload) => {
            nextState.deleteStatusesByID[payload.eventID] = {
                status: LoadStatus.PENDING,
            };
            return nextState;
        }),
);
