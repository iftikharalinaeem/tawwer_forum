/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import EventParticipantsTabs from "@groups/events/ui/EventParticipantsTabs";
import React from "react";
import { EventParticipantsModule } from "@groups/events/modules/EventParticipantsModule";
import { EventAttendance } from "@groups/events/state/eventsTypes";
import { t } from "@vanilla/i18n";
import { IEventDetailState, IEventDetailActionType } from "@groups/events/ui/EventDetails";

interface IProps {
    eventID: number;
    detailState: IEventDetailState;
    dispatchDetail: (value: any) => void;
}

export function EventParticipantsTabModule(props: IProps) {
    const { eventID, dispatchDetail, detailState } = props;
    const { visibleModal, goingPage, notGoingPage, maybePage, defaultTabIndex } = detailState;

    return (
        <EventParticipantsTabs
            defaultIndex={defaultTabIndex}
            isVisible={visibleModal}
            onClose={() => dispatchDetail({ type: IEventDetailActionType.SET_VISIBLE_MODAL, visible: false })}
            tabs={[
                {
                    title: t("Going"),
                    body: (
                        <EventParticipantsModule
                            page={goingPage}
                            setPage={page =>
                                dispatchDetail({ type: IEventDetailActionType.SET_GOING_PAGE, page: page })
                            }
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
                            setPage={page =>
                                dispatchDetail({ type: IEventDetailActionType.SET_MAYBE_PAGE, page: page })
                            }
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
                            setPage={page =>
                                dispatchDetail({ type: IEventDetailActionType.SET_NOT_GOING_PAGE, page: page })
                            }
                            eventID={eventID}
                            attendanceStatus={EventAttendance.NOT_GOING}
                        />
                    ),
                },
            ]}
        />
    );
}
