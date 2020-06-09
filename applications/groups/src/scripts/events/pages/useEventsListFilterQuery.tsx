/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import { useMemo, useCallback } from "react";
import { useHistory, useLocation } from "react-router";
import { EventFilterTypes } from "@groups/events/ui/EventsFilter";
import { LocationDescriptorObject } from "history";

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
