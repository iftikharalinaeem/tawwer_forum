import React, { useState } from "react";
import { EventParticipants } from "@groups/events/ui/EventParticipants";
import { useEventParticipants } from "../state/eventsHooks";
import { LoadStatus } from "@vanilla/library/src/scripts/@types/api/core";
import Loader from "@vanilla/library/src/scripts/loaders/Loader";
import ErrorMessages from "@vanilla/library/src/scripts/forms/ErrorMessages";
import { notEmpty } from "@vanilla/utils";

interface IProps {
    eventID: number;
}

export function EventParticipantsModule(props: IProps) {
    // const participants = useEventParticipants(props.eventID);
    const participants = useEventParticipants(1);

    if ([LoadStatus.PENDING, LoadStatus.LOADING].includes(participants.status)) {
        return <Loader />;
    }

    if (!participants.data || participants.error) {
        return <ErrorMessages errors={[participants.error].filter(notEmpty)} />;
    }

    console.log(participants);

    return <EventParticipants participants={participants.data.participants} />;
}
