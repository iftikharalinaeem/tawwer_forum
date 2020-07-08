/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { IEventParticipant } from "@groups/events/state/eventsTypes";
import { t } from "@vanilla/i18n";
import FrameBody from "@vanilla/library/src/scripts/layout/frame/FrameBody";
import { eventsClasses } from "./eventStyles";
import { eventParticipantsClasses } from "@groups/events/ui/eventParticipantsStyles";
import { UserPhoto, UserPhotoSize } from "@library/headers/mebox/pieces/UserPhoto";
import Button from "@vanilla/library/src/scripts/forms/Button";
import ProfileLink from "@library/navigation/ProfileLink";
import { UserCardModuleLazyLoad } from "@library/features/users/modules/UserCardModuleLazyLoad";

interface IProps {
    participants: IEventParticipant[];
    loadMore: () => void;
    showLoadMore?: number;
}

function Participant({ user }) {
    const classes = eventsClasses();

    const participantsClasses = eventParticipantsClasses();
    return (
        <ProfileLink username={user.name} className={participantsClasses.item}>
            <UserCardModuleLazyLoad
                userID={user.userID}
                buttonContent={
                    <UserPhoto className={classes.attendeePhoto} size={UserPhotoSize.MEDIUM} userInfo={user} />
                }
                openAsModal={true}
            />
            <span className={participantsClasses.name}>{user.name}</span>
        </ProfileLink>
    );
}

function Participants({ participants }) {
    const participantsClasses = eventParticipantsClasses();
    return (
        <ul className={participantsClasses.list}>
            {participants &&
                participants.map(participant => <Participant key={participant.userID} user={participant.user} />)}
        </ul>
    );
}

export default function EventParticipants(props: IProps) {
    const { participants, loadMore, showLoadMore } = props;
    const participantsClasses = eventParticipantsClasses();

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
                <div className={participantsClasses.tabsBottomButtonWrapper}>
                    <Button onClick={loadMore} style={{ width: 208 }}>
                        Load more
                    </Button>
                </div>
            )}
        </>
    );
}
