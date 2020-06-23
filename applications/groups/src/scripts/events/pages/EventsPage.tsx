/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { EventsModule } from "@groups/events/modules/EventsModule";
import { EventsPagePlaceholder } from "@groups/events/pages/EventsPagePlaceholder";
import { useEventsListFilterQuery } from "@groups/events/pages/useEventsListFilterQuery";
import { useEventParentRecord, useEventsList, useQueryParamPage } from "@groups/events/state/eventsHooks";
import EventFilter, { useEventQueryForFilter } from "@groups/events/ui/EventsFilter";
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
import { IGetEventsQuery, EventsActions } from "@groups/events/state/EventsActions";
import { ErrorPage } from "@vanilla/library/src/scripts/errorPages/ErrorComponent";
import { CoreErrorMessages } from "@vanilla/library/src/scripts/errorPages/CoreErrorMessages";
import Translate from "@library/content/Translate";

export default function EventsPage() {
    const params = useParams<{ parentRecordType?: string; parentRecordID?: string }>();
    const parentRecordType = params.parentRecordType ?? "category";
    const parentRecordID = params.parentRecordID !== null ? parseInt(params.parentRecordID!) : -1;

    const page = useQueryParamPage();
    const { filter, changeFilter } = useEventsListFilterQuery(page);

    const dateQuery = useEventQueryForFilter(filter);
    const eventQuery: IGetEventsQuery = {
        ...dateQuery,
        parentRecordType,
        parentRecordID,
        limit: EventsActions.DEFAULT_LIMIT,
        page,
        requireDescendants: true,
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
        return <CoreErrorMessages apiError={eventList.error ?? eventParent.error} />;
    }

    const parentRecordSlug = slugify(eventParent.data.name);
    const title = eventParent.data.name ?? "Events";

    return (
        <>
            <PageHeading
                title={<Translate source={"<0/> - Events"} c0={title} />}
                includeBackLink={false}
                headingClassName={classes.pageTitle}
            />
            <EventFilter className={classes.filter} filter={filter} onFilterChange={changeFilter} />
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
