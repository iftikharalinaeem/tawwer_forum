import ReduxActions, { bindThunkAction, useReduxActions } from "@library/redux/ReduxActions";
import { IApiError } from "@library/@types/api/core";
import { actionCreatorFactory } from "typescript-fsa";
import { IEventParticipant } from "@groups/events/state/EventReducer";
import { IEvent } from "@groups/events/state/eventsTypes";

const createAction = actionCreatorFactory("@@events");

type IEventParticipantsResponse = IEventParticipant[];

type IEventPatchRequest = {
    parentRecordID: string;
    parentRecordType: string;
    name?: string;
    body?: string;
    format?: string;
    location?: string;
    dateStarts?: string;
    dateEnds?: string;
};

type IEventPostParticipant = {
    id: number;
    attending: string; //enum
    userID?: number;
};

export class EventActions extends ReduxActions {
    public static readonly getEvent_ACS = createAction.async<{ id: number }, IEvent, IApiError>("GET_EVENT");
    public static readonly deleteEvent_ACS = createAction.async<{ id: number }, null, IApiError>("DELETE_EVENT");
    public static readonly patchEvent_ACS = createAction.async<IEventPostParticipant, IEvent, IApiError>("PATCH_EVENT");
    public static readonly getEventParticipants_ACS = createAction.async<
        { id: number },
        IEventParticipantsResponse,
        IApiError
    >("GET_EVENT_PARTICIPANTS");
    public static readonly postEventParticipants_ACS = createAction.async<
        IEventPostParticipant,
        IEventParticipant,
        IApiError
    >("POST_EVENT_PARTICIPANTS");

    public getEventByID = (id: number) => {
        const thunk = bindThunkAction(EventActions.getEvent_ACS, async () => {
            const response = await this.api.get(`/events/${id}?expand=all`);
            return response.data;
        })();
        return this.dispatch(thunk);
    };

    public getEventParticipantsByEventID = (id: number) => {
        const thunk = bindThunkAction(EventActions.getEventParticipants_ACS, async () => {
            const response = await this.api.get(`/events/${id}/participants?expand=true`);
            return response.data;
        })();
        return this.dispatch(thunk);
    };

    public postEventParticipants = async (params: IEventPostParticipant) => {
        const { id } = params;

        const thunk = bindThunkAction(EventActions.postEventParticipants_ACS, async () => {
            const response = await this.api.post(`/events/${id}/participants`, params);
            return response.data;
        })();
        return this.dispatch(thunk);
    };

    public deleteEvent = (id: number) => {
        const thunk = bindThunkAction(EventActions.deleteEvent_ACS, async () => {
            const response = await this.api.delete(`/events/${id}`);
            return response.data;
        })();
        return this.dispatch(thunk);
    };

    public patchEvent = (id: number) => {
        const thunk = bindThunkAction(EventActions.deleteEvent_ACS, async () => {
            const response = await this.api.delete(`/events/${id}`);
            return response.data;
        })();
        return this.dispatch(thunk);
    };
}

export function useEventActions() {
    return useReduxActions(EventActions);
}
