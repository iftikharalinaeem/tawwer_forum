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

export default function EventsPage() {
    const query = useQuery();
    const params = useParams<{ parentRecordType?: string; parentRecordID?: string }>();
    const parentRecordType = params.parentRecordType ?? "category";
    const parentRecordID = params.parentRecordID !== null ? parseInt(params.parentRecordID!) : -1;
    const filterValue = query.get("filter");
    const filter = useMemo(() => {
        if (!Object.values(EventFilterTypes as any).includes(filterValue)) {
            return EventFilterTypes.UPCOMING;
        } else {
            return filterValue as EventFilterTypes;
        }
    }, [filterValue]);
    const history = useHistory();

    const dateQuery = useDatesForEventFilter(filter);
    const eventQuery = { ...dateQuery, parentRecordType, parentRecordID };

    const eventList = useEventsList(eventQuery);
    const eventParent = useEventParentRecord({ parentRecordType, parentRecordID });
    const classes = eventsClasses();

    const pageTop = (
        <>
            <PageHeading title={t("Events")} includeBackLink={false} headingClassName={classes.pageTitle} />
            <EventFilter
                filter={filter}
                key={filter}
                onFilterChange={newFilter => {
                    const newParams = {
                        filter: newFilter,
                    };
                    const newQueryString = new URLSearchParams(newParams).toString();
                    const newLocation: LocationDescriptorObject = {
                        ...history.location,
                        search: newQueryString,
                    };
                    history.replace(newLocation);
                }}
            />
        </>
    );

    if (
        [LoadStatus.PENDING, LoadStatus.LOADING].includes(eventList.status) ||
        [LoadStatus.PENDING, LoadStatus.LOADING].includes(eventParent.status)
    ) {
        return (
            <>
                {pageTop}
                <EventListPlaceholder count={10} />
            </>
        );
    }

    if (!eventList.data || !eventParent.data || eventList.error || eventParent.error) {
        return <ErrorMessages errors={[eventList.error, eventParent.error].filter(notEmpty)} />;
    }

    const parentRecordSlug = slugify(eventParent.data.name);

    return (
        <>
            {pageTop}
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

// A custom hook that builds on useLocation to parse
// the query string for you.
function useQuery() {
    return new URLSearchParams(useLocation().search);
}
