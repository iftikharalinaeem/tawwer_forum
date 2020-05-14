/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { useMemo, useCallback } from "react";
import { PageHeading } from "@vanilla/library/src/scripts/layout/PageHeading";
import { useHistory, useLocation } from "react-router";
import EventFilter, { EventFilterTypes } from "@groups/events/ui/EventsFilter";
import { LocationDescriptorObject } from "history";
import { eventsClasses } from "@groups/events/ui/eventStyles";
import { t } from "@vanilla/i18n";
import { EventListPlaceholder } from "@groups/events/ui/EventListPlaceholder";

export function EventsPagePlaceholder() {
    const classes = eventsClasses();
    const { filter, changeFilter } = useEventsListFilterQuery();

    return (
        <>
            <PageHeading title={t("Events")} includeBackLink={false} headingClassName={classes.pageTitle} />
            <EventFilter filter={filter} onFilterChange={changeFilter} />
            <EventListPlaceholder count={10} />
        </>
    );
}

export function useEventsListFilterQuery(page: number = 1) {
    const history = useHistory();
    const query = new URLSearchParams(useLocation().search);

    const filterValue = query.get("filter");
    const filter = useMemo(() => {
        if (!Object.values(EventFilterTypes as any).includes(filterValue)) {
            return EventFilterTypes.UPCOMING;
        } else {
            return filterValue as EventFilterTypes;
        }
    }, [filterValue]);

    const changeFilter = useCallback(
        (newFilter: EventFilterTypes) => {
            const newParams = {
                filter: newFilter,
                page: page.toString(),
            };
            const newQueryString = new URLSearchParams(newParams).toString();
            const newLocation: LocationDescriptorObject = {
                ...history.location,
                search: newQueryString,
            };
            history.replace(newLocation);
        },
        [history, page],
    );

    return { filter, changeFilter };
}
