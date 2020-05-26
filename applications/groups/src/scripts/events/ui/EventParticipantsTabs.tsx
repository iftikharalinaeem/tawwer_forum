import React, { useState } from "react";
import { Tabs, TabList, Tab, TabPanels, TabPanel } from "@reach/tabs";
import { eventsClasses } from "@groups/events/ui/eventStyles";
import { CloseTinyIcon } from "@library/icons/common";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { UserPhoto, UserPhotoSize } from "@library/headers/mebox/pieces/UserPhoto";
import classNames from "classnames";
import { EventAttendance, IEventParticipant } from "@groups/events/state/eventsTypes";

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

export interface IParticipantData {
    eventID: number;
    userID: number;
    user: {
        userID: number;
        name: string;
        photoUrl: string;
        attending: EventAttendance;
    };
}

interface IProps {
    yesParticipants: IEventParticipant[];
    maybeParticipants: IEventParticipant[];
    noParticipants: IEventParticipant[];
    tabIndex: number;
    handleTabsChange: (index: number) => void;
    closeClick: () => void;
    loadMore: () => void;
}

export default function EventParticipantsTabs(props: IProps) {
    const classes = eventsClasses();
    const {
        yesParticipants,
        maybeParticipants,
        noParticipants,
        tabIndex,
        handleTabsChange,
        loadMore,
        closeClick,
    } = props;

    return (
        <Tabs defaultIndex={0} index={tabIndex} onChange={handleTabsChange} className={classes.participantsTabsRoot}>
            <div className={classes.participantsTabsTopButtonWrapper}>
                <Button
                    onClick={closeClick}
                    baseClass={ButtonTypes.CUSTOM}
                    className={classes.participantsTabsTopButton}
                >
                    <CloseTinyIcon />
                </Button>
            </div>
            <TabList className={classes.participantsTabsList}>
                <Tab className={classes.participantsTabsTab}>Going</Tab>
                <Tab className={classes.participantsTabsTab}>Maybe</Tab>
                <Tab className={classes.participantsTabsTab}>Not going</Tab>
            </TabList>
            <TabPanels className={classes.participantsTabsPanels}>
                <TabPanel>
                    <Participants participants={yesParticipants} />
                </TabPanel>
                <TabPanel>
                    <Participants participants={maybeParticipants} />
                </TabPanel>
                <TabPanel>
                    <Participants participants={noParticipants} />
                </TabPanel>
            </TabPanels>

            <div className={classes.participantsTabsBottomButtonWrapper}>
                <Button onClick={loadMore} style={{ width: 208 }}>
                    Load more
                </Button>
            </div>
        </Tabs>
    );
}
