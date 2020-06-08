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
    visibleModal: boolean;
    close: () => void;
}

export function EventParticipantsTabModule(props: IProps) {
    const { eventID, close, visibleModal, defaultIndex } = props;

    return (
        <EventParticipantsTabs
            defaultIndex={defaultIndex}
            isVisible={visibleModal}
            onClose={close}
            tabs={[
                {
                    title: t("Going"),
                    body: <EventParticipantsModule eventID={eventID} attendanceStatus={EventAttendance.GOING} />,
                },
                {
                    title: t("Maybe"),
                    body: <EventParticipantsModule eventID={eventID} attendanceStatus={EventAttendance.MAYBE} />,
                },
                {
                    title: t("Not Going"),
                    body: <EventParticipantsModule eventID={eventID} attendanceStatus={EventAttendance.NOT_GOING} />,
                },
            ]}
        />
    );
}
