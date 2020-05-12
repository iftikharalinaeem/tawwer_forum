/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { IGetEventsQuery } from "@groups/events/state/EventsActions";
import { EventsList } from "@vanilla/library/src/scripts/events/EventsList";
import { useEventsList, useEventParentRecord } from "@groups/events/state/eventsHooks";
import { LoadStatus } from "@vanilla/library/src/scripts/@types/api/core";
import { LoadingRectange, LoadingSpacer } from "@vanilla/library/src/scripts/loaders/LoadingRectangle";
import ErrorMessages from "@vanilla/library/src/scripts/forms/ErrorMessages";
import { notEmpty } from "@vanilla/utils";

interface IProps {
    query: IGetEventsQuery;
}

export function EventsModule(props: IProps) {
    const events = useEventsList(props.query);
    const eventParent = useEventParentRecord(props.query);

    if (
        [LoadStatus.PENDING, LoadStatus.LOADING].includes(events.status) ||
        [LoadStatus.PENDING, LoadStatus.LOADING].includes(eventParent.status)
    ) {
        return (
            <div>
                <LoadingRectange height={12} />
                <LoadingSpacer height={6} />
                <LoadingRectange height={12} />
            </div>
        );
    }

    if (!events.data || !eventParent.data || events.error || eventParent.error) {
        return <ErrorMessages errors={[events.error, eventParent.error].filter(notEmpty)} />;
    }

    return (
        <EventsList hideIfEmpty={true} data={events.data.events.map(event => ({ ...event, date: event.dateStarts }))} />
    );
}
