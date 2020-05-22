import React, { useState } from "react";
import Modal from "@vanilla/library/src/scripts/modal/Modal";
import ModalSizes from "@vanilla/library/src/scripts/modal/ModalSizes";
import { Tabs } from "@library/sectioning/Tabs";
import { TabsTypes } from "@library/sectioning/TabsTypes";
import { StoryTextContent } from "@vanilla/library/src/scripts/storybook/storyData";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";

export default function EventParticipants({ participants }) {
    const [isVisible, setIsVisible] = useState(true);
    const close = () => setIsVisible(false);

    return (
        <>
            <Button onClick={() => setIsVisible(true)} baseClass={ButtonTypes.PRIMARY}>
                Participants
            </Button>
            <Modal isVisible={isVisible} size={ModalSizes.MEDIUM} exitHandler={close}>
                <Tabs
                    tabType={TabsTypes.BROWSE}
                    data={[
                        {
                            label: "Tab 1",
                            panelData: "",
                            contents: <StoryTextContent firstTitle={"Hello Tab 1"} />,
                        },
                        {
                            label: "Tab 2",
                            panelData: "",
                            contents: <StoryTextContent firstTitle={"Hello Tab 2"} />,
                        },
                        {
                            label: "Tab 3",
                            panelData: "",
                            contents: <StoryTextContent firstTitle={"Hello Tab 3"} />,
                        },
                    ]}
                />
            </Modal>
        </>

        // <ul>
        //     {participants && participants.map(participant => <li key={participant.userID}> {participant.userID} </li>)}
        // </ul>
    );
}
