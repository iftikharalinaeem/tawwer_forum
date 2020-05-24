import React from "react";
import { Tabs, TabList, Tab, TabPanels, TabPanel } from "@reach/tabs";
import { eventsClasses } from "@groups/events/ui/eventStyles";
import { CloseTinyIcon } from "@library/icons/common";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { UserPhoto, UserPhotoSize } from "@library/headers/mebox/pieces/UserPhoto";

function Participant() {
    return (
        <li
            style={{
                display: "flex",
                flexDirection: "row",
                alignItems: "center",
                backgroundColor: "green",
                marginBottom: "19px",
            }}
        >
            <UserPhoto
                style={{
                    display: "inline-block",
                    marginRight: "16px",
                }}
                size={UserPhotoSize.MEDIUM}
            />
            Marie Curie
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
            <div style={{ position: "absolute", backgroundColor: "red", right: "6px", top: "10px" }}>
                <Button
                    baseClass={ButtonTypes.CUSTOM}
                    style={{
                        width: "24p",
                        height: "24px",
                        display: "inline-flex",
                        alignItems: "center",
                        justifyItems: "center",
                    }}
                >
                    <CloseTinyIcon />
                </Button>
            </div>
            <TabList
                style={{
                    height: "45px",
                    paddingTop: "12px",
                    paddingBottom: "13px",
                    fontWeight: "bold",
                    borderBottom: "solid 1px #dddee0",
                    marginBottom: "5px",
                    // backgroundColor: "red",
                }}
            >
                <Tab style={{ marginLeft: "16px", width: "106px", paddingLeft: "0px", textAlign: "left" }}>Going</Tab>
                <Tab style={{ width: "106px", paddingLeft: "0px", textAlign: "left" }}>Maybe</Tab>
                <Tab style={{ width: "106px", paddingLeft: "0px", textAlign: "left" }}>Not going</Tab>
                {/* <Tab style={{ marginLeft: "4em" }}> Hello </Tab> */}
            </TabList>
            <TabPanels style={{ overflowY: "scroll", height: "75%" }}>
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

            <div style={{ position: "absolute", textAlign: "center", bottom: "32px", left: "0", right: "0" }}>
                <Button style={{ width: "208px" }}> Load more </Button>
            </div>
        </Tabs>
    );
}
