/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { EventsModule } from "@groups/events/modules/EventsModule";
import { useEventParentRecord, useEventsList } from "@groups/events/state/eventsHooks";
import { EventListPlaceholder } from "@groups/events/ui/EventListPlaceholder";
import EventFilter, { EventFilterTypes, useDatesForEventFilter } from "@groups/events/ui/EventsFilter";
import { eventsClasses } from "@groups/events/ui/eventStyles";
import { LoadStatus } from "@library/@types/api/core";
import { t } from "@vanilla/i18n";
import ErrorMessages from "@vanilla/library/src/scripts/forms/ErrorMessages";
import SimplePager from "@vanilla/library/src/scripts/navigation/SimplePager";
import { formatUrl } from "@vanilla/library/src/scripts/utility/appUtils";
import { notEmpty, slugify } from "@vanilla/utils";
import React, { useMemo } from "react";
import { useHistory, useLocation, useParams } from "react-router";
import { LocationDescriptorObject } from "history";
import { PageHeading } from "@library/layout/PageHeading";
import { useEventsListFilterQuery, EventsPagePlaceholder } from "@groups/events/pages/EventsPagePlaceholder";

export default function EventsPage() {
    const params = useParams<{ parentRecordType?: string; parentRecordID?: string }>();
    const parentRecordType = params.parentRecordType ?? "category";
    const parentRecordID = params.parentRecordID !== null ? parseInt(params.parentRecordID!) : -1;

    const { filter, changeFilter } = useEventsListFilterQuery();

    const dateQuery = useDatesForEventFilter(filter);
    const eventQuery = { ...dateQuery, parentRecordType, parentRecordID };

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
