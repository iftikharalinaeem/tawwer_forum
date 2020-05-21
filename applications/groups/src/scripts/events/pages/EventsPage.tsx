/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { EventsModule } from "@groups/events/modules/EventsModule";
import {
    EventParticipantsModule,
    EventParticipantsByAttendanceModule,
} from "@groups/events/modules/EventParticipantsModule";
import { EventsPagePlaceholder, useEventsListFilterQuery } from "@groups/events/pages/EventsPagePlaceholder";
import { useEventParentRecord, useEventsList, useQueryParamPage } from "@groups/events/state/eventsHooks";
import EventFilter, { useDatesForEventFilter } from "@groups/events/ui/EventsFilter";
import { eventsClasses } from "@groups/events/ui/eventStyles";
import { LoadStatus } from "@library/@types/api/core";
import { PageHeading } from "@library/layout/PageHeading";
import { t } from "@vanilla/i18n";
import ErrorMessages from "@vanilla/library/src/scripts/forms/ErrorMessages";
import SimplePager from "@vanilla/library/src/scripts/navigation/SimplePager";
import { formatUrl } from "@vanilla/library/src/scripts/utility/appUtils";
import { notEmpty, slugify } from "@vanilla/utils";
import React, { useState } from "react";
import { useParams, useLocation } from "react-router";
import { IGetEventsQuery, EventsActions, useEventsActions } from "@groups/events/state/EventsActions";
import { EventAttendance } from "../state/eventsTypes";

export default function EventsPage() {
    const page = useQueryParamPage();
    const params = useParams<{ parentRecordType?: string; parentRecordID?: string }>();
    const parentRecordType = params.parentRecordType ?? "category";
    const parentRecordID = params.parentRecordID !== null ? parseInt(params.parentRecordID!) : -1;

    const { filter, changeFilter } = useEventsListFilterQuery(page);

    const dateQuery = useDatesForEventFilter(filter);
    const eventQuery: IGetEventsQuery = {
        ...dateQuery,
        parentRecordType,
        parentRecordID,
        limit: EventsActions.DEFAULT_LIMIT,
        page,
    };

    const eventList = useEventsList(eventQuery);
    const eventParent = useEventParentRecord({ parentRecordType, parentRecordID });

    const { getEventParticipants } = useEventsActions();
    const [participantsQuery, setParticipantsQuery] = useState({
        eventID: 2,
        limit: EventsActions.DEFAULT_PARTICIPANTS_LIMIT,
        page: 1,
    });

    const { getEventParticipantsByAttendance } = useEventsActions();

    const [tpquery, setTPQuery] = useState({
        eventID: 1,
        attending: EventAttendance.GOING,
        limit: EventsActions.DEFAULT_PARTICIPANTS_LIMIT,
        page: 1,
    });

    const [ttpquery, setTTPQuery] = useState({
        eventID: 1,
        attending: EventAttendance.MAYBE,
        limit: EventsActions.DEFAULT_PARTICIPANTS_LIMIT,
        page: 1,
    });

    const classes = eventsClasses();
    if (
        [LoadStatus.PENDING, LoadStatus.LOADING].includes(eventList.status) ||
        [LoadStatus.PENDING, LoadStatus.LOADING].includes(eventParent.status)
    ) {
        return <EventsPagePlaceholder />;
    }

    if (!eventList.data || !eventParent.data || eventList.error || eventParent.error) {
        return <ErrorMessages errors={[eventList.error, eventParent.error].filter(notEmpty)} />;
    }

    const parentRecordSlug = slugify(eventParent.data.name);

    return (
        <>
            <EventParticipantsModule query={participantsQuery} />
            <button
                type="button"
                onClick={() => {
                    const newQuery = { ...participantsQuery, page: participantsQuery.page + 1 };
                    setParticipantsQuery(newQuery);
                    getEventParticipants(newQuery);
                }}
            >
                Next
            </button>
            <br />
            <br />
            <br />
            <EventParticipantsByAttendanceModule query={tpquery} />

            <button
                type="button"
                onClick={() => {
                    const newQuery = { ...tpquery, page: tpquery.page + 1 };
                    // console.log(newQuery);
                    setTPQuery(newQuery);
                    getEventParticipantsByAttendance(newQuery);
                }}
            >
                Next
            </button>

            <br />
            <br />
            <br />
            <EventParticipantsByAttendanceModule query={ttpquery} />

            <button
                type="button"
                onClick={() => {
                    const newQuery = { ...ttpquery, page: ttpquery.page + 1 };
                    // console.log(newQuery);
                    setTTPQuery(newQuery);
                    getEventParticipantsByAttendance(newQuery);
                }}
            >
                Next
            </button>

            <PageHeading title={t("Events")} includeBackLink={false} headingClassName={classes.pageTitle} />
            <EventFilter filter={filter} onFilterChange={changeFilter} />
            <EventsModule query={eventQuery} />
            <SimplePager
                url={formatUrl(
                    `/events/${parentRecordType}/${parentRecordID}-${parentRecordSlug}?page=:page:&filter=${filter}`,
                    true,
                )}
                pages={eventList.data.pagination}
            />
        </>
    );
}
