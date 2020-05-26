/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import EventParticipantsTabs from "@groups/events/ui/EventParticipantsTabs";
import React, { useState } from "react";
import { IGetEventParticipantsQuery } from "../state/EventsActions";
import { EventParticipantsModule } from "@groups/events/modules/EventParticipantsModule";
import { EventAttendance } from "@groups/events/state/eventsTypes";
import { t } from "@vanilla/i18n";

interface IProps {
    eventID: number;
}

export function EventParticipantsTabModule(props: IProps) {
    const { eventID } = props;
    const [isVisible, setIsVisible] = useState(true);

    return (
        <EventParticipantsTabs
            isVisible={isVisible}
            onClose={() => setIsVisible(false)}
            tabs={[
                {
                    title: t("Going"),
                    body: <EventParticipantsModule eventID={eventID} attendanceStatus={EventAttendance.GOING} />,
                },
                {
                    title: t("Not Going"),
                    body: <EventParticipantsModule eventID={eventID} attendanceStatus={EventAttendance.NOT_GOING} />,
                },
                {
                    title: t("Maybe"),
                    body: <EventParticipantsModule eventID={eventID} attendanceStatus={EventAttendance.MAYBE} />,
                },
            ]}
        />
    );
}
