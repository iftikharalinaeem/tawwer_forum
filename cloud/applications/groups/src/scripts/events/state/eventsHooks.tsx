/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import { useSelector } from "react-redux";
import { IEventsStoreState } from "@groups/events/state/EventsReducer";
import {
    IGetEventsQuery,
    useEventsActions,
    IGetEventParentRecordQuery,
    IGetEventParticipantsQuery,
    IGetEventParticipantsByAttendanceQuery,
} from "@groups/events/state/EventsActions";
import { stableObjectHash } from "@vanilla/utils";
import { LoadStatus } from "@vanilla/library/src/scripts/@types/api/core";
import { useEffect, useReducer, useCallback, useDebugValue } from "react";
import { EventAttendance } from "@groups/events/state/eventsTypes";
import { useLocation } from "react-router";
import { action } from "@storybook/addon-actions";
import { original } from "immer";

export function useEventsState() {
    return useSelector((state: IEventsStoreState) => {
        return state.events;
    });
}

export function useEventsList(params: IGetEventsQuery) {
    const actions = useEventsActions();
    const hash = stableObjectHash(params);
    const existingResult = useSelector((state: IEventsStoreState) => {
        return (
            state.events.eventsLists[hash] ?? {
                status: LoadStatus.PENDING,
            }
        );
    });

    const { status } = existingResult;

    useEffect(() => {
        if ([LoadStatus.PENDING].includes(status)) {
            actions.getEventList(params);
        }
        // Using the hash instead of the object
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [status, actions, hash]);

    return existingResult;
}

export function useEventParticipants(query: IGetEventParticipantsQuery) {
    const actions = useEventsActions();

    const existingResult = useSelector((state: IEventsStoreState) => {
        return (
            state.events.participantsByEventID[query.eventID] ?? {
                status: LoadStatus.PENDING,
            }
        );
    });

    const { status } = existingResult;

    useEffect(() => {
        if ([LoadStatus.PENDING].includes(status)) {
            actions.getEventParticipants(query);
        }
    }, [status, actions, query]);

    return existingResult;
}

export function useEventParticipantsByAttendance(query: IGetEventParticipantsByAttendanceQuery) {
    const actions = useEventsActions();
    const { eventID, attending } = query;
    const hash = stableObjectHash({ eventID, attending });

    const existingResult = useSelector((state: IEventsStoreState) => {
        return (
            state.events.participantsByAttendanceByEventID[hash] ?? {
                status: LoadStatus.PENDING,
            }
        );
    });

    const { status } = existingResult;
    useEffect(() => {
        if ([LoadStatus.PENDING].includes(status)) {
            actions.getEventParticipantsByAttendance(query);
        }
    }, [status, actions, query]);

    return existingResult;
}

export function useEventParentRecord(params: IGetEventParentRecordQuery) {
    const actions = useEventsActions();
    const { parentRecordID, parentRecordType } = params;
    const existingResult = useSelector((state: IEventsStoreState) => {
        return (
            state.events.eventParentRecords[parentRecordType + parentRecordID] ?? {
                status: LoadStatus.PENDING,
            }
        );
    });

    const { status } = existingResult;

    useEffect(() => {
        if ([LoadStatus.PENDING].includes(status)) {
            actions.getEventParentRecord({ parentRecordID, parentRecordType });
        }
    }, [status, actions, parentRecordID, parentRecordType]);

    return existingResult;
}

export function useEvent(eventID: number) {
    const actions = useEventsActions();

    const existingResult = useSelector((state: IEventsStoreState) => {
        return (
            state.events.eventsByID[eventID] ?? {
                status: LoadStatus.PENDING,
            }
        );
    });
    const { status } = existingResult;

    useEffect(() => {
        if ([LoadStatus.PENDING].includes(status)) {
            actions.getEventByID(eventID);
        }
    }, [status, actions, eventID]);

    useDebugValue(existingResult);

    return existingResult;
}

export function useEventAttendance(eventID: number) {
    const actions = useEventsActions();

    const setEventAttendance = useCallback(
        (attending: EventAttendance) => {
            actions.postEventParticipants({ eventID, attending });
        },
        [actions, eventID],
    );

    const setEventAttendanceLoadable = useSelector((state: IEventsStoreState) => {
        return (
            state.events.partipateStatusByEventID[eventID] ?? {
                status: LoadStatus.PENDING,
            }
        );
    });

    return { setEventAttendance, setEventAttendanceLoadable };
}

export function useQueryParamPage(): number {
    const query = new URLSearchParams(useLocation().search);
    const queryPage = query.get("page");

    if (!queryPage) {
        return 1;
    }

    const parsed = parseInt(queryPage, 10);
    if (Number.isNaN(parsed)) {
        return 1;
    }

    return parsed;
}