/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { EventsModule } from "@groups/events/modules/EventsModule";
import { EventParticipantsModule } from "@groups/events/modules/EventParticipantsModule";
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
import React from "react";
import { useParams, useLocation } from "react-router";
import { IGetEventsQuery, EventsActions } from "@groups/events/state/EventsActions";

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
            <EventParticipantsModule />
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
