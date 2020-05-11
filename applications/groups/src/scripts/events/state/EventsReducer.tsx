import produce from "immer";
import { IUserFragment } from "@library/@types/api/users";
import { reducerWithInitialState } from "typescript-fsa-reducers";
import { ILoadable, LoadStatus } from "@library/@types/api/core";
import { useSelector } from "react-redux";
import { ICoreStoreState } from "@library/redux/reducerRegistry";
import { EventsActions } from "@groups/events/state/EventsActions";

export interface IEvent {
    eventID: number;
    name: string;
    body: string;
    format: string;
    parentRecordType: string; //enum?
    parentRecordID: number;
    dateStarts: string;
    dateEnds: string;
    allDayEvent: boolean;
    location: string;
    dateInserted: string;
    dateUpdated?: string;
    attending?: string;
    insertUser: IUserFragment;
    updatedUser: IUserFragment;
    groupID?: number;
    url: string;
}
export interface IEventsState {
    events: ILoadable<{
        events: IEvent[];
    }>;
}

export interface IEventsStoreState extends ICoreStoreState {
    events: IEventsState;
}

const DEFAULT_EVENT_STATE: IEventsState = {
    events: {
        status: LoadStatus.PENDING,
    },
};

export const eventsReducer = produce(
    reducerWithInitialState<IEventsState>(DEFAULT_EVENT_STATE)
        .case(EventsActions.getAllEvents_ACS.started, (nextState, payload) => {
            nextState.events.status = LoadStatus.LOADING;
            return nextState;
        })
        .case(EventsActions.getAllEvents_ACS.done, (nextState, payload) => {
            nextState.events.status = LoadStatus.SUCCESS;
            nextState.events.data = { events: payload.result };

            return nextState;
        })
        .case(EventsActions.getAllEvents_ACS.failed, (nextState, payload) => {
            nextState.events.status = LoadStatus.ERROR;
            nextState.events.error = payload.error;
            return nextState;
        }),
);

export function useEventsState() {
    return useSelector((state: IEventsStoreState) => {
        return state.events;
    });
}
