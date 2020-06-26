/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { IGetEventsQuery } from "@groups/events/state/EventsActions";
import { useEventsList, useEventParentRecord } from "@groups/events/state/eventsHooks";
import { LoadStatus } from "@vanilla/library/src/scripts/@types/api/core";
import { LoadingRectange, LoadingSpacer } from "@vanilla/library/src/scripts/loaders/LoadingRectangle";
import ErrorMessages from "@vanilla/library/src/scripts/forms/ErrorMessages";
import { notEmpty } from "@vanilla/utils";
import { EventList } from "@groups/events/ui/EventList";
import { EventListPlaceholder } from "@groups/events/ui/EventListPlaceholder";

interface IProps {
    query: IGetEventsQuery;
}

export function EventsModule(props: IProps) {
    const events = useEventsList(props.query);

    if ([LoadStatus.PENDING, LoadStatus.LOADING].includes(events.status)) {
        return <EventListPlaceholder count={5} />;
    }

    if (!events.data || events.error) {
        return <ErrorMessages errors={[events.error].filter(notEmpty)} />;
    }

    return <EventList hideIfEmpty={true} events={events.data.events} />;
}
