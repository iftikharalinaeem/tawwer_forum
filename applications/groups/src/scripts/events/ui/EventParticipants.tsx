/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { IEventParticipant } from "@groups/events/state/eventsTypes";
import { t } from "@vanilla/i18n";
import FrameBody from "@vanilla/library/src/scripts/layout/frame/FrameBody";
import { eventsClasses } from "./eventStyles";
import { UserPhoto, UserPhotoSize } from "@library/headers/mebox/pieces/UserPhoto";
import Button from "@vanilla/library/src/scripts/forms/Button";

interface IProps {
    participants: IEventParticipant[];
    loadMore: () => void;
    showLoadMore: number | undefined;
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

export default function EventParticipants(props: IProps) {
    const { participants, loadMore, showLoadMore } = props;
    const classes = eventsClasses();

    if (participants.length === 0) {
        return (
            <FrameBody>
                <p>{t("No Event Participants.")}</p>
            </FrameBody>
        );
    }

    return (
        <>
            <Participants participants={participants} />
            {showLoadMore && (
                <div className={classes.participantsTabsBottomButtonWrapper}>
                    <Button onClick={loadMore} style={{ width: 208 }}>
                        Load more
                    </Button>
                </div>
            )}
        </>
    );
}
