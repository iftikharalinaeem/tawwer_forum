/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import EventParticipantsTabs from "@groups/events/ui/EventParticipantsTabs";
import React from "react";
import { EventParticipantsModule } from "@groups/events/modules/EventParticipantsModule";
import { EventAttendance } from "@groups/events/state/eventsTypes";
import { t } from "@vanilla/i18n";

interface IProps {
    eventID: number;
    defaultIndex: number;
    // visibleModal: boolean;
    // pages: {};
    state: any;
    dispatch: (value: any) => void;
}

export function EventParticipantsTabModule(props: IProps) {
    const { eventID, defaultIndex, dispatch, state } = props;
    const { visibleModal, goingPage, notGoingPage, maybePage } = state;
    console.log(state);

    return (
        <EventParticipantsTabs
            defaultIndex={defaultIndex}
            isVisible={visibleModal}
            onClose={() => dispatch({ type: "set_visible_modal", visible: false })}
            tabs={[
                {
                    title: t("Going"),
                    body: (
                        <EventParticipantsModule
                            page={goingPage}
                            setPage={page => dispatch({ type: "set_going_page", page: page })}
                            eventID={eventID}
                            attendanceStatus={EventAttendance.GOING}
                        />
                    ),
                },
                {
                    title: t("Maybe"),
                    body: (
                        <EventParticipantsModule
                            page={maybePage}
                            setPage={page => dispatch({ type: "set_maybe_page", page: page })}
                            eventID={eventID}
                            attendanceStatus={EventAttendance.MAYBE}
                        />
                    ),
                },
                {
                    title: t("Not Going"),
                    body: (
                        <EventParticipantsModule
                            page={notGoingPage}
                            setPage={page => dispatch({ type: "set_not_going_page", page: page })}
                            eventID={eventID}
                            attendanceStatus={EventAttendance.NOT_GOING}
                        />
                    ),
                },
            ]}
        />
    );
}
