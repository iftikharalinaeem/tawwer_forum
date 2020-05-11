/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useEffect } from "react";
import { LoadStatus } from "@library/@types/api/core";
import { useParams } from "react-router";
import { useEventActions } from "@groups/events/state/EventActions";
import { useEventState } from "@groups/events/state/EventReducer";
import { EventDetails } from "@library/events/EventDetails";
import Loader from "@library/loaders/Loader";
import { eventAttendanceOptions } from "@library/events/eventOptions";

export default function EventPage() {
    const { getEventByID, getEventParticipantsByEventID, postEventParticipants } = useEventActions();

    let eventID = useParams<{
        id: string;
    }>().id;

    const eventState = useEventState();
    let event = eventState.event;
    let participants = eventState.eventParticipants;

    useEffect(() => {
        if (event.status === LoadStatus.PENDING || event.status === LoadStatus.LOADING) {
            getEventByID(parseInt(eventID));
            getEventParticipantsByEventID(parseInt(eventID));
        }
    }, [event.data]);

    console.log(event.data);
    console.log(participants.data);

    const eventCreator = event.data?.insertUser;
    const dateStart = event.data?.dateStarts;
    const name = event.data?.name;
    const location = event.data?.location;
    const url = event.data?.url;
    const attendance = event.data?.attending;

    if (
        !event.data ||
        !participants.data ||
        event.status === LoadStatus.LOADING ||
        event.status === LoadStatus.PENDING
    ) {
        return <Loader />;
    } else {
        const going = participants?.data.filter(participant => {
            return participant.attending === "yes";
        });

        const notGoing = participants?.data.filter(participant => {
            return participant.attending === "no";
        });

        const maybe = participants?.data.filter(participant => {
            return participant.attending === "maybe";
        });

        return (
            <EventDetails
                organizer={eventCreator.name}
                dateStart={dateStart}
                name={name}
                location={location}
                url={url}
                attendance={attendance}
                attendanceOptions={eventAttendanceOptions}
            />
        );
    }
}
