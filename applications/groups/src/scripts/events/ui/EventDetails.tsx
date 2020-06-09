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
import ButtonTab from "@library/forms/buttonTabs/ButtonTab";
import { ButtonTabs } from "@library/forms/buttonTabs/ButtonTabs";
import { t } from "@vanilla/i18n/src";
import React, { useReducer } from "react";
import { IEvent, EventAttendance, EventPermissionName } from "@groups/events/state/eventsTypes";

import SmartLink from "@vanilla/library/src/scripts/routing/links/SmartLink";
import { makeProfileUrl } from "@vanilla/library/src/scripts/utility/appUtils";
import { EventPermission } from "@groups/events/state/EventPermission";
import classNames from "classnames";
import { EventParticipantsTabModule } from "@groups/events/modules/EventParticipantsTabModule";

interface IProps {
    event: IEvent;
    organizer: string;
    going?: IUserFragment[] | undefined;
    maybe?: IUserFragment[] | undefined;
    notGoing?: IUserFragment[] | undefined;
    onChange: (data: any) => void;
    loadingAttendance?: EventAttendance;
}

/**
 * Component for displaying an event details
 */
export function EventDetails(props: IProps) {
    const classes = eventsClasses();
    const { event } = props;

    const initialState = {
        visibleModal: false,
        goingPage: 1,
        maybePage: 1,
        notGoingPage: 1,
        defaultTabIndex: undefined,
    };

    const reducer = (state, action) => {
        switch (action.type) {
            case "set_visible_modal":
                return { ...state, visibleModal: action.visible };
            case "set_going_page":
                return { ...state, goingPage: action.page };
            case "set_maybe_page":
                return { ...state, maybePage: action.page };
            case "set_not_going_page":
                return { ...state, notGoingPage: action.page };
            case "set_default_tab_index":
                return { ...state, defaultTabIndex: action.index };
            default:
                return state;
        }
    };

    const [state, dispatch] = useReducer(reducer, initialState);

    return (
        <div className={classes.details}>
            <EventParticipantsTabModule state={state} eventID={event.eventID} dispatch={dispatch} />
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
                            <SmartLink className={classes.organizer} to={makeProfileUrl(event.insertUser.name)}>
                                {event.insertUser.name}
                            </SmartLink>
                        ),
                    },
                ]}
                className={classNames(classes.section, classes.firstSection)}
                caption={t("Event Details")}
            />
            <EventPermission event={event} permission={EventPermissionName.ATTEND}>
                <ButtonTabs
                    activeTab={props.loadingAttendance ?? props.event.attending ?? EventAttendance.RSVP}
                    accessibleTitle={t("Are you going?")}
                    setData={props.onChange}
                    className={classes.attendanceSelector}
                >
                    <ButtonTab
                        disabled={!!props.loadingAttendance}
                        label={t("Going")}
                        isLoading={props.loadingAttendance === EventAttendance.GOING}
                        data={EventAttendance.GOING}
                    />
                    <ButtonTab
                        disabled={!!props.loadingAttendance}
                        label={t("Maybe")}
                        data={EventAttendance.MAYBE}
                        isLoading={props.loadingAttendance === EventAttendance.MAYBE}
                    />
                    <ButtonTab
                        disabled={!!props.loadingAttendance}
                        label={t("Not going")}
                        data={EventAttendance.NOT_GOING}
                        isLoading={props.loadingAttendance === EventAttendance.NOT_GOING}
                        className={"isLast"}
                    />
                </ButtonTabs>
            </EventPermission>

            <div className={classes.section}>
                <hr className={classes.separator} />
                <h2 className={classes.sectionTitle}>{t("About the event")}</h2>
                <UserContent className={classes.description} content={event.body} />
            </div>

            <EventAttendees
                eventID={event.eventID}
                data={props.going!}
                title={t("Going")}
                emptyMessage={t("Nobody has confirmed their attendance yet.")}
                extra={props.going?.length}
                separator={true}
                dispatch={dispatch}
            />
            <EventAttendees
                eventID={event.eventID}
                emptyMessage={t("Nobody is on the fence right now.")}
                data={props.maybe!}
                title={t("Maybe")}
                extra={props.maybe?.length}
                separator={true}
                dispatch={dispatch}
            />
            <EventAttendees
                eventID={event.eventID}
                emptyMessage={t("Nobody has declined the invitation so far.")}
                data={props.notGoing!}
                title={t("Not going")}
                extra={props.notGoing?.length}
                separator={true}
                dispatch={dispatch}
            />
        </div>
    );
}
