import React from "react";
import EventParticipants from "@groups/events/ui/EventParticipants";

export default {
    component: EventParticipants,
    title: "Event Participants",
};

export const FullParticipantList = () => {
    return (
        <>
            <EventParticipants participants={[]} />
        </>
    );
};
