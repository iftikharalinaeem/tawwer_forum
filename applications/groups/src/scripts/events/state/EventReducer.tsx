import produce from "immer";
import { IUserFragment } from "@library/@types/api/users";
import { reducerWithInitialState } from "typescript-fsa-reducers";
import { ILoadable, LoadStatus } from "@library/@types/api/core";
import { useSelector } from "react-redux";
import { ICoreStoreState } from "@library/redux/reducerRegistry";
import { EventActions } from "@groups/events/state/EventActions";
import { IEvent } from "@groups/events/state/eventsTypes";

export interface IEventParticipant {
    attending: string;
    dateInserted: string;
    eventID: number;
    user?: IUserFragment;
    userID: number;
}

export interface IEventState {
    event: ILoadable<IEvent>;
    eventParticipants: ILoadable<IEventParticipant[]>;
    participant: ILoadable<IEventParticipant>;
    deleteEvent: ILoadable<undefined>;
}

export interface IEventsStoreState extends ICoreStoreState {
    event: IEventState;
}

const DEFAULT_EVENT_STATE: IEventState = {
    event: {
        status: LoadStatus.PENDING,
    },
    eventParticipants: {
        status: LoadStatus.PENDING,
    },
    participant: {
        status: LoadStatus.PENDING,
    },
    deleteEvent: {
        status: LoadStatus.PENDING,
    },
};

export const eventReducer = produce(
    reducerWithInitialState<IEventState>(DEFAULT_EVENT_STATE)
        .case(EventActions.getEvent_ACS.started, (nextState, payload) => {
            nextState.event.status = LoadStatus.LOADING;
            return nextState;
        })
        .case(EventActions.getEvent_ACS.done, (nextState, payload) => {
            nextState.event.status = LoadStatus.SUCCESS;
            nextState.event.data = payload.result;

            return nextState;
        })
        .case(EventActions.getEvent_ACS.failed, (nextState, payload) => {
            nextState.event.status = LoadStatus.ERROR;
            nextState.event.error = payload.error;
            return nextState;
        })
        .case(EventActions.getEventParticipants_ACS.started, (nextState, payload) => {
            nextState.eventParticipants.status = LoadStatus.LOADING;
            return nextState;
        })
        .case(EventActions.getEventParticipants_ACS.done, (nextState, payload) => {
            nextState.eventParticipants.status = LoadStatus.SUCCESS;
            nextState.eventParticipants.data = payload.result;

            return nextState;
        })
        .case(EventActions.getEventParticipants_ACS.failed, (nextState, payload) => {
            nextState.eventParticipants.status = LoadStatus.ERROR;
            nextState.eventParticipants.error = payload.error;
            return nextState;
        })
        .case(EventActions.postEventParticipants_ACS.started, (nextState, payload) => {
            nextState.participant.status = LoadStatus.LOADING;
            return nextState;
        })
        .case(EventActions.postEventParticipants_ACS.done, (nextState, payload) => {
            nextState.participant.status = LoadStatus.SUCCESS;
            nextState.event.data.attending = payload.result.attending;

            return nextState;
        })
        .case(EventActions.postEventParticipants_ACS.failed, (nextState, payload) => {
            nextState.participant.status = LoadStatus.ERROR;
            nextState.participant.error = payload.error;
            return nextState;
        })
        .case(EventActions.deleteEvent_ACS.started, (nextState, payload) => {
            nextState.deleteEvent.status = LoadStatus.LOADING;
            return nextState;
        })
        .case(EventActions.deleteEvent_ACS.done, (nextState, payload) => {
            nextState.deleteEvent.status = LoadStatus.SUCCESS;
            return nextState;
        })
        .case(EventActions.deleteEvent_ACS.failed, (nextState, payload) => {
            nextState.deleteEvent.status = LoadStatus.ERROR;
            nextState.deleteEvent.error = payload.error;
            return nextState;
        }),
);

export function useEventState() {
    return useSelector((state: IEventsStoreState) => {
        return state.event;
    });
}
