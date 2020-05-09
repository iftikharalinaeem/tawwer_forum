import ReduxActions, { bindThunkAction, useReduxActions } from "@library/redux/ReduxActions";
import { IApiError } from "@library/@types/api/core";
import { actionCreatorFactory } from "typescript-fsa";
import { IEvent } from "applications/groups/src/scripts/events/state/EventsReducer";
import { History } from "history";

const createAction = actionCreatorFactory("@@events");

type IGetEvents = {
    parentRecordType: string;
    parentRecordID: number;
    dateStarts?: string;
    dateEnds?: string;
};

type IGetEventsResponse = IEvent[];

export class EventsActions extends ReduxActions {
    public static readonly getAllEvents_ACS = createAction.async<{}, IGetEventsResponse, IApiError>("GET_EVENTS");

    public getEvents = async () => {
        return await this.getAllEvents({ parentRecordID: 3, parentRecordType: "group" });
    };

    public getAllEvents = (params: IGetEvents) => {
        const thunk = bindThunkAction(EventsActions.getAllEvents_ACS, async () => {
            const response = await this.api.get(`/events/`, { params });
            return response.data;
        })();
        return this.dispatch(thunk);
    };
}

export function useEventsActions() {
    return useReduxActions(EventsActions);
}
