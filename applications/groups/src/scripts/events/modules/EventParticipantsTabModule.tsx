/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import EventParticipantsTabs from "@groups/events/ui/EventParticipantsTabs";
import React, { useState } from "react";
import { EventParticipantsModule } from "@groups/events/modules/EventParticipantsModule";
import { EventAttendance } from "@groups/events/state/eventsTypes";
import { t } from "@vanilla/i18n";

interface IProps {
    eventID: number;
    visible: boolean;
}

export function EventParticipantsTabModule(props: IProps) {
    const { eventID, visible } = props;
    const [isVisible, setIsVisible] = useState(visible);

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
