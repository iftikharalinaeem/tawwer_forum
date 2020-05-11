/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useEffect } from "react";
import { useEventsActions } from "@groups/events/state/EventsActions";
import { useEventsState } from "@groups/events/state/EventsReducer";
import { LoadStatus } from "@library/@types/api/core";
import { useParams } from "react-router";

export default function EventsPage() {
    const { getEvents } = useEventsActions();
    const eventsState = useEventsState();
    let events = eventsState.events.data?.events;

    let { parentRecordType, parentRecordID } = useParams();

    useEffect(() => {
        if (eventsState.events.status === LoadStatus.PENDING || eventsState.events.status === LoadStatus.LOADING) {
            getEvents();
        }
    });

    {
        /*<EventList data={events} />;*/
    }

    return <div>test</div>;
}
