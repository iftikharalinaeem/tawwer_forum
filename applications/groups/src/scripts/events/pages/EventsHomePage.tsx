/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import { getEventPath } from "@groups/routes/EventsRoutes";
import { Tabs } from "@vanilla/library/src/scripts/sectioning/Tabs";
import { TabsTypes } from "@vanilla/library/src/scripts/sectioning/TabsTypes";
import { LocationDescriptorObject } from "history";
import React from "react";
import { useHistory, useLocation, useParams } from "react-router";
import { useQueryParamPage } from "@groups/events/state/eventsHooks";
import { useEventsListFilterQuery } from "@groups/events/pages/useEventsListFilterQuery";
import EventFilter from "@groups/events/ui/EventsFilter";

export default function EventsHomePage() {
    const params = useParams<{ parentRecordType: string }>();
    const history = useHistory();
    const location = useLocation();

    const page = useQueryParamPage();
    const { filter, changeFilter } = useEventsListFilterQuery(page);
    const { parentRecordType } = params;

    return (
        <div>
            <Tabs
                tabType={TabsTypes.BROWSE}
                largeTabs
                extendContainer
                extraButtons={<EventFilter filter={filter} onFilterChange={changeFilter} />}
                onChange={newTab => {
                    const newLocation: LocationDescriptorObject = {
                        ...location,
                        pathname: getEventPath(`/${newTab.parentRecordType}`),
                    };
                    history.push(newLocation);
                }}
                data={[
                    {
                        parentRecordType: "category",
                        label: "Community Events",
                        contents: (
                            <>
                                Hello events home <strong>Categories</strong>
                            </>
                        ),
                    },
                    {
                        parentRecordType: "group",
                        label: "Group Events",
                        contents: (
                            <>
                                Hello events home <strong>Groups</strong>
                            </>
                        ),
                    },
                ]}
            />
        </div>
    );
}
