import React from "react";
import { IEventParticipant } from "@groups/events/state/eventsTypes";
import { t } from "@vanilla/i18n";
import FrameBody from "@vanilla/library/src/scripts/layout/frame/FrameBody";
import { eventsClasses } from "./eventStyles";
import { UserPhoto, UserPhotoSize } from "@library/headers/mebox/pieces/UserPhoto";

interface IProps {
    participants: IEventParticipant[];
}

function Participant({ user }) {
    const classes = eventsClasses();
    return (
        <li className={classes.participantItem}>
            <UserPhoto className={classes.attendeePhoto} size={UserPhotoSize.MEDIUM} userInfo={user} />
            <span className={classes.participantName}>{user.name}</span>
        </li>
    );
}

function Participants({ participants }) {
    const classes = eventsClasses();
    return (
        <ul className={classes.participantList}>
            {participants &&
                participants.map(participant => <Participant key={participant.userID} user={participant.user} />)}
        </ul>
    );
}

export default function EventParticipants({ participants }: IProps) {
    if (participants.length === 0) {
        return (
            <FrameBody>
                <p>{t("No Event Participants.")}</p>
            </FrameBody>
        );
    }

    return <Participants participants={participants} />;
}
