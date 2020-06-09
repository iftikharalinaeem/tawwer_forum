/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { useState, useEffect } from "react";
import { Tabs, TabList, Tab, TabPanels, TabPanel } from "@reach/tabs";
import { eventParticipantsClasses } from "@groups/events/ui/eventParticipantsStyles";
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
    const classes = eventParticipantsClasses();
    const { onClose, tabs, isVisible, defaultIndex } = props;

    const [tabIndex, setTabIndex] = useState(defaultIndex);

    useEffect(() => {
        setTabIndex(defaultIndex);
    }, [isVisible]);

    return (
        <Modal scrollable={true} isVisible={isVisible} size={ModalSizes.MEDIUM} exitHandler={props.onClose}>
            <Tabs index={tabIndex} onChange={setTabIndex} className={classes.tabsRoot}>
                <div className={classes.tabsTopButtonWrapper}>
                    <Button onClick={onClose} baseClass={ButtonTypes.ICON} className={classes.tabsTopButton}>
                        <CloseTinyIcon />
                    </Button>
                </div>
                <TabList className={classes.tabsList}>
                    {tabs.map((tab, i) => {
                        return (
                            <Tab key={i} className={classes.tabsTab}>
                                {tab.title}
                            </Tab>
                        );
                    })}
                </TabList>
                <TabPanels className={classes.tabsPanels}>
                    {tabs.map((tab, i) => {
                        return <TabPanel key={i}>{tab.body}</TabPanel>;
                    })}
                </TabPanels>
            </Tabs>
        </Modal>
    );
}
