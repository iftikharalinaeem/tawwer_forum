/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import { EventsActions, IGetEventsQuery } from "@groups/events/state/EventsActions";
import { useEventsList } from "@groups/events/state/eventsHooks";
import { EventList } from "@groups/events/ui/EventList";
import { EventListPlaceholder } from "@groups/events/ui/EventListPlaceholder";
import { EventFilterTypes, useEventQueryForFilter } from "@groups/events/ui/EventsFilter";
import { t } from "@vanilla/i18n";
import { LoadStatus } from "@vanilla/library/src/scripts/@types/api/core";
import { CoreErrorMessages } from "@vanilla/library/src/scripts/errorPages/CoreErrorMessages";
import SimplePager from "@vanilla/library/src/scripts/navigation/SimplePager";
import { formatUrl, getSiteSection } from "@vanilla/library/src/scripts/utility/appUtils";
import React from "react";

interface IProps {
    parentRecordType: string;
    page: number;
    filterType: EventFilterTypes;
}

export function EventsHomePageTab(props: IProps) {
    const { parentRecordType, page, filterType } = props;
    const dateQuery = useEventQueryForFilter(filterType);

    const siteSection = getSiteSection();
    const query: IGetEventsQuery = { parentRecordType, page, ...dateQuery, limit: EventsActions.DEFAULT_LIMIT };
    if (props.parentRecordType === "category" && siteSection.attributes.CategoryID) {
        query.parentRecordID = siteSection.attributes.CategoryID;
        query.requireDescendants = true;
    }

    const eventsList = useEventsList(query);

    if ([LoadStatus.PENDING, LoadStatus.LOADING].includes(eventsList.status)) {
        return <EventListPlaceholder count={10} />;
    }

    if (!eventsList.data || eventsList.error) {
        return <CoreErrorMessages apiError={eventsList.error} />;
    }

    return (
        <>
            <EventList emptyMessage={t("No events found.")} events={eventsList.data.events} />
            <SimplePager
                url={formatUrl(`/events/${parentRecordType}?page=:page:&filter=${filterType}`, true)}
                pages={eventsList.data.pagination}
            />
        </>
    );
}
