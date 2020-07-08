/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { IUserFragment } from "@library/@types/api/users";
import { FromToDateTime } from "@library/content/FromToDateTime";
import UserContent from "@library/content/UserContent";
import { DataList } from "@library/dataLists/DataList";
import { EventAttendees } from "@groups/events/ui/Attendees";
import { eventsClasses } from "@groups/events/ui/eventStyles";
import { t } from "@vanilla/i18n/src";
import React, { useReducer } from "react";
import { IEvent, EventAttendance, EventPermissionName } from "@groups/events/state/eventsTypes";
import SmartLink from "@vanilla/library/src/scripts/routing/links/SmartLink";
import { makeProfileUrl } from "@vanilla/library/src/scripts/utility/appUtils";
import { EventPermission } from "@groups/events/state/EventPermission";
import classNames from "classnames";
import { EventParticipantsTabModule } from "@groups/events/modules/EventParticipantsTabModule";
import RadioInputAsButton from "@library/forms/radioAsButtons/RadioInputAsButton";
import { RadioGroup } from "@vanilla/library/src/scripts/forms/radioAsButtons/RadioGroup";
import { radioInputAsButtonsClasses } from "@library/forms/radioAsButtons/radioInputAsButtons.styles";
import { UserCardModuleLazyLoad } from "@vanilla/library/src/scripts/features/users/modules/UserCardModuleLazyLoad";

interface IAttendees {
    users: IUserFragment[] | undefined;
    count: number | undefined;
}

interface IProps {
    event: IEvent;
    organizer: string;
    going?: IAttendees;
    maybe?: IAttendees;
    notGoing?: IAttendees;
    onChange: (data: any) => void;
    loadingAttendance?: EventAttendance;
}

/**
 * Component for displaying an event details
 */
export function EventDetails(props: IProps) {
    const classes = eventsClasses();
    const { event, going, maybe, notGoing } = props;

    const [state, dispatch] = useEventDetailReducer();

    return (
        <div className={classes.details}>
            <EventParticipantsTabModule detailState={state} eventID={event.eventID} dispatchDetail={dispatch} />
            <DataList
                data={[
                    {
                        key: t("When"),
                        value: <FromToDateTime dateStarts={event.dateStarts} dateEnds={event.dateEnds} />,
                    },
                    {
                        key: t("Where"),
                        value: event.location,
                    },
                    {
                        key: t("Organizer"),
                        value: (
                            <UserCardModuleLazyLoad
                                userID={event.insertUser.userID}
                                buttonContent={event.insertUser.name}
                            />
                        ),
                    },
                ]}
                className={classNames(classes.section)}
                caption={t("Event Details")}
            />
            <EventPermission event={event} permission={EventPermissionName.ATTEND}>
                <RadioGroup
                    activeItem={props.loadingAttendance ?? props.event.attending ?? EventAttendance.RSVP}
                    accessibleTitle={t("Are you going?")}
                    setData={props.onChange}
                    classes={radioInputAsButtonsClasses()}
                >
                    <>
                        <RadioInputAsButton
                            disabled={!!props.loadingAttendance}
                            label={t("Going")}
                            isLoading={props.loadingAttendance === EventAttendance.GOING}
                            data={EventAttendance.GOING}
                        />
                        <RadioInputAsButton
                            disabled={!!props.loadingAttendance}
                            label={t("Maybe")}
                            data={EventAttendance.MAYBE}
                            isLoading={props.loadingAttendance === EventAttendance.MAYBE}
                        />
                        <RadioInputAsButton
                            disabled={!!props.loadingAttendance}
                            label={t("Not going")}
                            data={EventAttendance.NOT_GOING}
                            isLoading={props.loadingAttendance === EventAttendance.NOT_GOING}
                            className={"isLast"}
                        />
                    </>
                </RadioGroup>
            </EventPermission>

            <div className={classes.section}>
                <hr className={classes.separator} />
                <h2 className={classes.sectionTitle}>{t("About the event")}</h2>
                <UserContent className={classes.description} content={event.body} />
            </div>

            <EventAttendees
                eventID={event.eventID}
                data={going?.users}
                title={t("Going")}
                emptyMessage={t("Nobody has confirmed their attendance yet.")}
                extra={going?.count}
                separator={true}
                dispatchDetail={dispatch}
            />
            <EventAttendees
                eventID={event.eventID}
                emptyMessage={t("Nobody is on the fence right now.")}
                data={maybe?.users}
                title={t("Maybe")}
                extra={maybe?.count}
                separator={true}
                dispatchDetail={dispatch}
            />
            <EventAttendees
                eventID={event.eventID}
                emptyMessage={t("Nobody has declined the invitation so far.")}
                data={notGoing?.users}
                title={t("Not going")}
                extra={notGoing?.count}
                separator={true}
                dispatchDetail={dispatch}
            />
        </div>
    );
}

export interface IEventDetailState {
    visibleModal: boolean;
    goingPage: number;
    maybePage: number;
    notGoingPage: number;
    defaultTabIndex: number;
}

export enum IEventDetailActionType {
    SET_VISIBLE_MODAL = "set_visible_modal",
    SET_GOING_PAGE = "set_going_page",
    SET_NOT_GOING_PAGE = "set_not_going_page",
    SET_MAYBE_PAGE = "set_maybe_page",
    SET_DEFAULT_TAB_INDEX = "set_default_tab_index",
}

type EventDetailAction =
    | {
          type: IEventDetailActionType.SET_VISIBLE_MODAL;
          visible: boolean;
      }
    | {
          type:
              | IEventDetailActionType.SET_GOING_PAGE
              | IEventDetailActionType.SET_MAYBE_PAGE
              | IEventDetailActionType.SET_NOT_GOING_PAGE;
          page: number;
      }
    | {
          type: IEventDetailActionType.SET_DEFAULT_TAB_INDEX;
          index: number;
      };

function useEventDetailReducer() {
    const initialState: IEventDetailState = {
        visibleModal: false,
        goingPage: 1,
        maybePage: 1,
        notGoingPage: 1,
        defaultTabIndex: 0,
    };

    const reducer = (state: IEventDetailState, action: EventDetailAction) => {
        switch (action.type) {
            case IEventDetailActionType.SET_VISIBLE_MODAL:
                return { ...state, visibleModal: action.visible };
            case IEventDetailActionType.SET_GOING_PAGE:
                return { ...state, goingPage: action.page };
            case IEventDetailActionType.SET_MAYBE_PAGE:
                return { ...state, maybePage: action.page };
            case IEventDetailActionType.SET_NOT_GOING_PAGE:
                return { ...state, notGoingPage: action.page };
            case IEventDetailActionType.SET_DEFAULT_TAB_INDEX:
                return { ...state, defaultTabIndex: action.index };
            default:
                return state;
        }
    };

    return useReducer(reducer, initialState);
}
