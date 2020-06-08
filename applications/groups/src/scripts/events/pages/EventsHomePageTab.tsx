/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { EventFilterTypes, useEventQueryForFilter } from "@groups/events/ui/EventsFilter";
import { useEventsList } from "@groups/events/state/eventsHooks";
import { EventsActions, IGetEventsQuery } from "@groups/events/state/EventsActions";
import { LoadStatus } from "@vanilla/library/src/scripts/@types/api/core";
import { EventsPagePlaceholder } from "@groups/events/pages/EventsPagePlaceholder";
import ErrorMessages from "@vanilla/library/src/scripts/forms/ErrorMessages";
import { notEmpty } from "@vanilla/utils";
import { EventList } from "@groups/events/ui/EventList";
import { t } from "@vanilla/i18n";
import SimplePager from "@vanilla/library/src/scripts/navigation/SimplePager";
import { formatUrl, getSiteSection } from "@vanilla/library/src/scripts/utility/appUtils";
import { EventListPlaceholder } from "@groups/events/ui/EventListPlaceholder";
import { CoreErrorMessages } from "@vanilla/library/src/scripts/errorPages/CoreErrorMessages";

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
