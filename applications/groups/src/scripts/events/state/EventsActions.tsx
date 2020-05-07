import ReduxActions from "@library/redux/ReduxActions";
import { IApiError } from "@library/@types/api/core";
import { actionCreatorFactory } from "typescript-fsa";
import { IDateTime } from "@library/content/DateTime";

const createAction = actionCreatorFactory("@@events");

interface IGetEvents {
    parentRecordType: string;
    dateStarts: IDateTime;
    dateEnds: IDateTime;
}

export class EventsActions extends ReduxActions {
    public static readonly getAllThemes_ACS = createAction.async<IGetEvents, IApiError>("GET_EVENTS");
}
