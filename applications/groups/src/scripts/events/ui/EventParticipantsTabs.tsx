/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { useState } from "react";
import { Tabs, TabList, Tab, TabPanels, TabPanel } from "@reach/tabs";
import { eventsClasses } from "@groups/events/ui/eventStyles";
import { CloseTinyIcon } from "@library/icons/common";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import Modal from "@vanilla/library/src/scripts/modal/Modal";
import ModalSizes from "@vanilla/library/src/scripts/modal/ModalSizes";

interface IProps {
    isVisible: boolean;
    onClose: () => void;
    tabs: Array<{
        title: string;
        body: React.ReactNode;
    }>;
    defaultIndex: number;
}

export default function EventParticipantsTabs(props: IProps) {
    const classes = eventsClasses();
    const { onClose, tabs, isVisible, defaultIndex } = props;

    const [tabIndex, setTabIndex] = useState(defaultIndex);

    return (
        <Modal isVisible={isVisible} size={ModalSizes.MEDIUM} exitHandler={props.onClose}>
            <Tabs
                defaultIndex={defaultIndex}
                index={tabIndex}
                onChange={setTabIndex}
                className={classes.participantsTabsRoot}
            >
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
