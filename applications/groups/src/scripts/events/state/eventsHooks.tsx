/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import { useSelector } from "react-redux";
import { IEventsStoreState } from "@groups/events/state/EventsReducer";
import { IGetEventsQuery, useEventsActions, IGetEventParentRecordQuery } from "@groups/events/state/EventsActions";
import { stableObjectHash } from "@vanilla/utils";
import { LoadStatus } from "@vanilla/library/src/scripts/@types/api/core";
import { useEffect } from "react";

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
