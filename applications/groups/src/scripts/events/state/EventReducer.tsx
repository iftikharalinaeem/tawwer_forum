import produce from "immer";
import { IUserFragment } from "@library/@types/api/users";
import { reducerWithInitialState } from "typescript-fsa-reducers";
import { ILoadable, LoadStatus } from "@library/@types/api/core";
import { useSelector } from "react-redux";
import { ICoreStoreState } from "@library/redux/reducerRegistry";
import { EventsActions } from "@groups/events/state/EventsActions";
import { IEvent } from "@groups/events/state/EventsReducer";
import { EventActions } from "@groups/events/state/EventActions";

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
        }),
);

export function useEventState() {
    return useSelector((state: IEventsStoreState) => {
        return state.event;
    });
}
