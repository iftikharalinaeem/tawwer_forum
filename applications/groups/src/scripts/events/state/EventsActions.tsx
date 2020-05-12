/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import ReduxActions, { bindThunkAction, useReduxActions } from "@library/redux/ReduxActions";
import { IApiError } from "@library/@types/api/core";
import { actionCreatorFactory } from "typescript-fsa";
import { IEventList, IEventParentRecord } from "@groups/events/state/eventsTypes";
import SimplePagerModel from "@vanilla/library/src/scripts/navigation/SimplePagerModel";

const createAction = actionCreatorFactory("@@events");

export interface IGetEventsQuery {
    parentRecordType: string;
    parentRecordID: number;
    dateStarts?: string;
    dateEnds?: string;
}

export interface IGetEventParentRecordQuery {
    parentRecordType: string;
    parentRecordID: number;
}

type IGetEventsResponse = IEventList;

export class EventsActions extends ReduxActions {
    public static readonly getEventListACs = createAction.async<IGetEventsQuery, IGetEventsResponse, IApiError>(
        "GET_EVENT_LIST",
    );

    public getEventList = (params: IGetEventsQuery) => {
        const thunk = bindThunkAction(EventsActions.getEventListACs, async () => {
            const response = await this.api.get(`/events?expand=permissions`, { params });
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
}

export function useEventsActions() {
    return useReduxActions(EventsActions);
}
