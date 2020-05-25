import React, { useState } from "react";
import EventParticipants from "@groups/events/ui/EventParticipants";
import { useEventParticipants, useEventParticipantsByAttendance } from "../state/eventsHooks";
import { LoadStatus } from "@vanilla/library/src/scripts/@types/api/core";
import Loader from "@vanilla/library/src/scripts/loaders/Loader";
import ErrorMessages from "@vanilla/library/src/scripts/forms/ErrorMessages";
import { notEmpty } from "@vanilla/utils";
import { IGetEventParticipantsQuery, IGetEventParticipantsByAttendanceQuery } from "../state/EventsActions";

interface IParticipantsProps {
    query: IGetEventParticipantsQuery;
}

interface IParticipantsByAttendanceProps {
    query: IGetEventParticipantsByAttendanceQuery;
}

export function EventParticipantsModule(props: IParticipantsProps) {
    const participants = useEventParticipants(props.query);

    if ([LoadStatus.PENDING, LoadStatus.LOADING].includes(participants.status) && !participants.data) {
        return <Loader />;
    }

    if (!participants.data || participants.error) {
        return <ErrorMessages errors={[participants.error].filter(notEmpty)} />;
    }

    return <EventParticipants participants={participants.data.participants} />;
}

export function EventParticipantsByAttendanceModule(props: IParticipantsByAttendanceProps) {
    const participants = useEventParticipantsByAttendance(props.query);

    if ([LoadStatus.PENDING, LoadStatus.LOADING].includes(participants.status) && !participants.data) {
        return <Loader />;
    }

    if (!participants.data || participants.error) {
        return <ErrorMessages errors={[participants.error].filter(notEmpty)} />;
    }

    return <EventParticipants participants={participants.data.participants} />;
}
