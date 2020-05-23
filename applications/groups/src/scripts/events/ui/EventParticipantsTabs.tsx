import React from "react";
import { Tabs, TabList, Tab, TabPanels, TabPanel } from "@reach/tabs";
import { eventsClasses } from "@groups/events/ui/eventStyles";
import { CloseTinyIcon } from "@library/icons/common";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";

export default function EventParticipantsTabs() {
    const classes = eventsClasses();

    return (
        <Tabs className={classes.participantsTabsRoot}>
            <Button
                baseClass={ButtonTypes.CUSTOM}
                style={{
                    position: "absolute",
                    // backgroundColor: "red",
                    right: "6px",
                    top: "10px",
                    width: "24p",
                    height: "24px",
                    display: "inline-flex",
                    alignItems: "center",
                    justifyItems: "center",
                }}
            >
                <CloseTinyIcon />
            </Button>

            <TabList
                style={{
                    height: "45px",
                    paddingTop: "12px",
                    paddingBottom: "13px",
                    fontWeight: "bold",
                    borderBottom: "solid 1px #dddee0",
                    // backgroundColor: "red",
                }}
            >
                <Tab style={{ marginLeft: "16px", width: "106px", paddingLeft: "0px", textAlign: "left" }}>Going</Tab>
                <Tab style={{ width: "106px", paddingLeft: "0px", textAlign: "left" }}>Maybe</Tab>
                <Tab style={{ width: "106px", paddingLeft: "0px", textAlign: "left" }}>Not going</Tab>
                {/* <Tab style={{ marginLeft: "4em" }}> Hello </Tab> */}
            </TabList>
            <TabPanels style={{ overflowY: "scroll", height: "75%" }}>
                <TabPanel style={{ marginLeft: "16px" }}>
                    <p>
                        Even though he was rarely an experimenter, Poincaré recognizes and defends the importance of
                        experimentation, which must remain a pillar of the scientific method. According to him, it is
                        not necessary that mathematics incorporate physics into itself, but must develop as an asset
                        unto itself. This asset would be above all a tool: in the words of Poincaré, mathematics is "the
                        only language in which [physicists] could speak" to understand each other and to make themselves
                        heard. This language of numbers seems elsewhere to reveal a unity hidden in the natural world,
                        when there may well be only one part of mathematics that applies to theoretical physics. The
                        primary objective of mathematical physics is not invention or discovery, but reformulation. It
                        is an activity of synthesis, which permits one to assure the coherence of theories current at a
                        given time. Poincaré recognized that it is impossible to systematize all of physics of a
                        specific time period into one axiomatic theory. His ideas of a three dimensional space are given
                        significance in this context.!
                    </p>

                    <p>
                        Even though he was rarely an experimenter, Poincaré recognizes and defends the importance of
                        experimentation, which must remain a pillar of the scientific method. According to him, it is
                        not necessary that mathematics incorporate physics into itself, but must develop as an asset
                        unto itself. This asset would be above all a tool: in the words of Poincaré, mathematics is "the
                        only language in which [physicists] could speak" to understand each other and to make themselves
                        heard. This language of numbers seems elsewhere to reveal a unity hidden in the natural world,
                        when there may well be only one part of mathematics that applies to theoretical physics. The
                        primary objective of mathematical physics is not invention or discovery, but reformulation. It
                        is an activity of synthesis, which permits one to assure the coherence of theories current at a
                        given time. Poincaré recognized that it is impossible to systematize all of physics of a
                        specific time period into one axiomatic theory. His ideas of a three dimensional space are given
                        significance in this context.!
                    </p>

                    <p>
                        Even though he was rarely an experimenter, Poincaré recognizes and defends the importance of
                        experimentation, which must remain a pillar of the scientific method. According to him, it is
                        not necessary that mathematics incorporate physics into itself, but must develop as an asset
                        unto itself. This asset would be above all a tool: in the words of Poincaré, mathematics is "the
                        only language in which [physicists] could speak" to understand each other and to make themselves
                        heard. This language of numbers seems elsewhere to reveal a unity hidden in the natural world,
                        when there may well be only one part of mathematics that applies to theoretical physics. The
                        primary objective of mathematical physics is not invention or discovery, but reformulation. It
                        is an activity of synthesis, which permits one to assure the coherence of theories current at a
                        given time. Poincaré recognized that it is impossible to systematize all of physics of a
                        specific time period into one axiomatic theory. His ideas of a three dimensional space are given
                        significance in this context.!
                    </p>
                </TabPanel>
                <TabPanel style={{ marginLeft: "16px" }}>
                    <p>two!</p>
                </TabPanel>
                <TabPanel style={{ marginLeft: "16px" }}>
                    <p>three!</p>
                </TabPanel>
            </TabPanels>
            <div style={{ position: "absolute", textAlign: "center", bottom: "32px", left: "0", right: "0" }}>
                <Button style={{ width: "208px" }}> Load more </Button>
            </div>
        </Tabs>
    );
}
