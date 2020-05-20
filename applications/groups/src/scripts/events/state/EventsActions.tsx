/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import ReduxActions, { bindThunkAction, useReduxActions } from "@library/redux/ReduxActions";
import { IApiError } from "@library/@types/api/core";
import { actionCreatorFactory } from "typescript-fsa";
import {
    IEventList,
    IEventParentRecord,
    IEventWithParticipants,
    IEvent,
    IEventParticipant,
    EventAttendance,
    IEventParticipantList,
} from "@groups/events/state/eventsTypes";
import SimplePagerModel from "@vanilla/library/src/scripts/navigation/SimplePagerModel";
import { number } from "prop-types";

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
    eventID: number;
    attending: EventAttendance;
    userID?: number;
};
export interface IGetEventsQuery {
    parentRecordType: string;
    parentRecordID: number;
    dateStarts?: string;
    dateEnds?: string;
    page: number;
    limit: number;
}

export interface IGetEventParticipantsQuery {
    eventID: number;
    limit: number;
    page?: number;
}

export interface IGetEventParentRecordQuery {
    parentRecordType: string;
    parentRecordID: number;
}

type IGetEventsResponse = IEventList;

export class EventsActions extends ReduxActions {
    public static DEFAULT_LIMIT = 1;
    public static DEFAULT_PARTICIPANTS_LIMIT = 1;

    public static readonly getEventListACs = createAction.async<IGetEventsQuery, IGetEventsResponse, IApiError>(
        "GET_EVENT_LIST",
    );

    public getEventList = (params: IGetEventsQuery) => {
        const thunk = bindThunkAction(EventsActions.getEventListACs, async () => {
            const response = await this.api.get(`/events`, { params: { ...params, expand: true } });
            const pagination = SimplePagerModel.parseLinkHeader(response.headers["link"], "page");
            const result: IEventList = {
                events: response.data,
                pagination,
            };
            return result;
        })(params);
        return this.dispatch(thunk);
    };

    public static readonly getEventParentRecord = createAction.async<
        IGetEventParentRecordQuery,
        IEventParentRecord,
        IApiError
    >("GET_EVENT_PARENT_RECORD");

    public getEventParentRecord = (params: IGetEventParentRecordQuery) => {
        const thunk = bindThunkAction(EventsActions.getEventParentRecord, async () => {
            if (params.parentRecordType === "category") {
                const response = await this.api.get(`/categories/${params.parentRecordID}?expand[]=breadcrumbs`);
                const { data } = response;
                const result: IEventParentRecord = {
                    name: data.name,
                    breadcrumbs: data.breadcrumbs,
                    parentRecordID: data.categoryID,
                    parentRecordType: "category",
                    url: data.url,
                    description: data.description,
                    bannerUrl: data.bannerUrl,
                    iconUrl: data.iconUrl,
                };
                return result;
            } else {
                const response = await this.api.get(`/groups/${params.parentRecordID}`);
                const { data } = response;
                const result: IEventParentRecord = {
                    name: data.name,
                    breadcrumbs: data.breadcrumbs,
                    parentRecordID: data.groupID,
                    parentRecordType: "category",
                    url: data.url,
                    description: data.description,
                    bannerUrl: data.bannerUrl,
                    iconUrl: data.iconUrl,
                };
                return result;
            }
        })(params);
        return this.dispatch(thunk);
    };

    public static readonly getEventParticipantsACs = createAction.async<
        { eventID: number },
        IEventParticipantList,
        IApiError
    >("GET_EVENT_PARTICIPANTS");

    public getEventParticipants = (params: IGetEventParticipantsQuery) => {
        const { eventID, limit } = params;
        const thunk = bindThunkAction(EventsActions.getEventParticipantsACs, async () => {
            const response = await this.api.get(`/events/${eventID}/participants`, {
                params: { ...params, expand: true },
            });
            const pagination = SimplePagerModel.parseLinkHeader(response.headers["link"], "page");
            const result: IEventParticipantList = {
                eventID: eventID,
                participants: response.data,
                pagination,
            };
            return result;
        })({ eventID });
        return this.dispatch(thunk);
    };

    public static readonly getEventACs = createAction.async<{ eventID: number }, IEventWithParticipants, IApiError>(
        "GET_EVENT_WITH_PARTICIPANTS",
    );
    public static readonly deleteEvent_ACS = createAction.async<{ eventID: number }, null, IApiError>("DELETE_EVENT");
    public static readonly patchEvent_ACS = createAction.async<IEventPostParticipant, IEvent, IApiError>("PATCH_EVENT");
    public static readonly postEventParticipants_ACS = createAction.async<
        IEventPostParticipant,
        IEventParticipant,
        IApiError
    >("POST_EVENT_PARTICIPANTS");

    public getEventByID = (eventID: number) => {
        const thunk = bindThunkAction(EventsActions.getEventACs, async () => {
            const [eventResponse, participantResponse] = await Promise.all([
                this.api.get(`/events/${eventID}?expand[]=all`),
                this.api.get(`/events/${eventID}/participants?expand=true`),
            ]);

            const result: IEventWithParticipants = {
                event: eventResponse.data,
                participants: participantResponse.data,
            };
            return result;
        })({ eventID });
        return this.dispatch(thunk);
    };

    public postEventParticipants = async (params: IEventPostParticipant) => {
        const { eventID } = params;

        const thunk = bindThunkAction(EventsActions.postEventParticipants_ACS, async () => {
            const response = await this.api.post(`/events/${eventID}/participants`, params);
            return response.data;
        })(params);
        return this.dispatch(thunk);
    };

    public static clearDeleteStatusAC = createAction<{ eventID: number }>("CLEAR_DELETE_STATUS");

    public clearDeleteStatus = this.bindDispatch(EventsActions.clearDeleteStatusAC);

    public deleteEvent = (eventID: number) => {
        const thunk = bindThunkAction(EventsActions.deleteEvent_ACS, async () => {
            const response = await this.api.delete(`/events/${eventID}`);
            return response.data;
        })({ eventID });
        return this.dispatch(thunk);
    };

    public patchEvent = (eventID: number) => {
        const thunk = bindThunkAction(EventsActions.deleteEvent_ACS, async () => {
            const response = await this.api.delete(`/events/${eventID}`);
            return response.data;
        })({ eventID });
        return this.dispatch(thunk);
    };
}

export function useEventsActions() {
    return useReduxActions(EventsActions);
}
