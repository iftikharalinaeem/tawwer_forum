import React from "react";
import { Tabs, TabList, Tab, TabPanels, TabPanel } from "@reach/tabs";
import { eventsClasses } from "@groups/events/ui/eventStyles";
import { CloseTinyIcon } from "@library/icons/common";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { UserPhoto, UserPhotoSize } from "@library/headers/mebox/pieces/UserPhoto";
import classNames from "classnames";

function Participant() {
    const classes = eventsClasses();
    return (
        <li
            style={{
                display: "flex",
                flexDirection: "row",
                alignItems: "center",
                // backgroundColor: "green",
                marginBottom: "19px",
            }}
        >
            <UserPhoto className={classes.attendeePhoto} size={UserPhotoSize.MEDIUM} />
            <span
                style={{
                    display: "inline-block",
                    marginLeft: "16px",
                }}
            >
                Marie Curie
            </span>
        </li>
    );
}

function Participants() {
    return (
        <ul style={{ marginLeft: "16px" }}>
            <Participant />
            <Participant />
            <Participant />
            <Participant />
            <Participant />
            <Participant />
            <Participant />
            <Participant />
            <Participant />
            <Participant />
            <Participant />
            <Participant />
            <Participant />
            <Participant />
        </ul>
    );
}

export default function EventParticipantsTabs() {
    const classes = eventsClasses();

    return (
        <Tabs className={classes.participantsTabsRoot}>
            <div className={classes.participantsTabsTopButtonWrapper}>
                <Button baseClass={ButtonTypes.CUSTOM} className={classes.participantsTabsTopButton}>
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
                    <Participants />
                </TabPanel>
                <TabPanel>
                    <Participants />
                </TabPanel>
                <TabPanel>
                    <Participants />
                </TabPanel>
            </TabPanels>

            <div className={classes.participantsTabsBottomButtonWrapper}>
                <Button style={{ width: "208px" }}> Load more </Button>
            </div>
        </Tabs>
    );
}
