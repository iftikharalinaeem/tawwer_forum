/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { PageHeading } from "@vanilla/library/src/scripts/layout/PageHeading";
import EventFilter from "@groups/events/ui/EventsFilter";
import { eventsClasses } from "@groups/events/ui/eventStyles";
import { t } from "@vanilla/i18n";
import { EventListPlaceholder } from "@groups/events/ui/EventListPlaceholder";
import { useEventsListFilterQuery } from "./useEventsListFilterQuery";

export function EventsPagePlaceholder() {
    const classes = eventsClasses();
    const { filter, changeFilter } = useEventsListFilterQuery();

    return (
        <>
            <PageHeading title={t("Events")} includeBackLink={false} headingClassName={classes.pageTitle} />
            <EventFilter className={classes.filter} filter={filter} onFilterChange={changeFilter} />
            <EventListPlaceholder count={10} />
        </>
    );
}
