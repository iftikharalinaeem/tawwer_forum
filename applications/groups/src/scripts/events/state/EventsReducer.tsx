/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import { EventsActions } from "@groups/events/state/EventsActions";
import { IEventList, IEventParentRecord } from "@groups/events/state/eventsTypes";
import { ILoadable, LoadStatus } from "@library/@types/api/core";
import { ICoreStoreState } from "@library/redux/reducerRegistry";
import { stableObjectHash } from "@vanilla/utils";
import produce from "immer";
import { reducerWithInitialState } from "typescript-fsa-reducers";

export interface IEventsState {
    eventsLists: Record<string, ILoadable<IEventList>>;
    eventParentRecords: Record<string, ILoadable<IEventParentRecord>>;
}

export interface IEventsStoreState extends ICoreStoreState {
    events: IEventsState;
}

const DEFAULT_EVENT_STATE: IEventsState = {
    eventsLists: {},
    eventParentRecords: {},
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
        }),
);
