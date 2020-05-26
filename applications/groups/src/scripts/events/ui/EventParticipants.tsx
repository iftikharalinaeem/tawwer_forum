import React from "react";
import { IEventParticipant } from "@groups/events/state/eventsTypes";
import { t } from "@vanilla/i18n";
import FrameBody from "@vanilla/library/src/scripts/layout/frame/FrameBody";

interface IProps {
    participants: IEventParticipant[];
}

export default function EventParticipants({ participants }: IProps) {
    if (participants.length === 0) {
        return (
            <FrameBody>
                <p>{t("No Event Participants.")}</p>
            </FrameBody>
        );
    }

    return (
        <ul>
            {participants &&
                participants.map(participant => (
                    <li key={participant.userID}>
                        {participant.user.name} - {participant.user.userID}
                    </li>
                ))}
        </ul>
    );
}
