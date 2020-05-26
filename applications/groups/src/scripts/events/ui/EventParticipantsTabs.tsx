import React, { useState } from "react";
import { Tabs, TabList, Tab, TabPanels, TabPanel } from "@reach/tabs";
import { eventsClasses } from "@groups/events/ui/eventStyles";
import { CloseTinyIcon } from "@library/icons/common";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { UserPhoto, UserPhotoSize } from "@library/headers/mebox/pieces/UserPhoto";
import classNames from "classnames";
import { EventAttendance, IEventParticipant } from "@groups/events/state/eventsTypes";
import Modal from "@vanilla/library/src/scripts/modal/Modal";
import ModalSizes from "@vanilla/library/src/scripts/modal/ModalSizes";

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
    isVisible: boolean;
    onClose: () => void;
    tabs: Array<{
        title: string;
        body: React.ReactNode;
    }>;
}

export default function EventParticipantsTabs(props: IProps) {
    const classes = eventsClasses();
    const { onClose, tabs } = props;

    const [tabIndex, setTabIndex] = useState(0);

    return (
        <Modal isVisible={props.isVisible} size={ModalSizes.MEDIUM} exitHandler={props.onClose}>
            <Tabs defaultIndex={0} index={tabIndex} onChange={setTabIndex} className={classes.participantsTabsRoot}>
                <div className={classes.participantsTabsTopButtonWrapper}>
                    <Button
                        onClick={onClose}
                        baseClass={ButtonTypes.ICON}
                        className={classes.participantsTabsTopButton}
                    >
                        <CloseTinyIcon />
                    </Button>
                </div>
                <TabList className={classes.participantsTabsList}>
                    {tabs.map((tab, i) => {
                        return (
                            <Tab key={i} className={classes.participantsTabsTab}>
                                {tab.title}
                            </Tab>
                        );
                    })}
                </TabList>
                <TabPanels className={classes.participantsTabsPanels}>
                    {tabs.map((tab, i) => {
                        return <TabPanel key={i}>{tab.body}</TabPanel>;
                    })}
                </TabPanels>
            </Tabs>
        </Modal>
    );
}
