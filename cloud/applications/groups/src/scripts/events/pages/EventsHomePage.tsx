/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import { getEventPath } from "@groups/routes/EventsRoutes";
import { Tabs } from "@vanilla/library/src/scripts/sectioning/Tabs";
import { TabsTypes } from "@vanilla/library/src/scripts/sectioning/TabsTypes";
import { LocationDescriptorObject } from "history";
import React, { useEffect } from "react";
import { useHistory, useLocation, useParams } from "react-router";
import { useQueryParamPage } from "@groups/events/state/eventsHooks";
import { useEventsListFilterQuery } from "@groups/events/pages/useEventsListFilterQuery";
import EventFilter from "@groups/events/ui/EventsFilter";
import { EventsHomePageTab } from "@groups/events/pages/EventsHomePageTab";
import Group from "react-select/lib/components/Group";
import { t } from "@vanilla/i18n";
import { EventsHomePagePlaceholder } from "@groups/events/pages/EventsHomePagePlaceholder";
import { PageHeading } from "@vanilla/library/src/scripts/layout/PageHeading";
import { eventsClasses } from "@groups/events/ui/eventStyles";
import { UserCardModule } from "@vanilla/library/src/scripts/features/users/modules/UserCardModule";

export default function EventsHomePage({ loading }: any) {
    const params = useParams<{ parentRecordType: string }>();
    const history = useHistory();
    const location = useLocation();
    const classes = eventsClasses();

    const page = useQueryParamPage();

    const { filter, changeFilter } = useEventsListFilterQuery(page);
    const { parentRecordType } = params;

    if (loading) {
        return <EventsHomePagePlaceholder />;
    }

    return (
        <div>
            <PageHeading title={t("Events")} includeBackLink={false} headingClassName={classes.pageTitle} />
            <UserCardModule userID={1} />
            <Tabs
                defaultTabIndex={parentRecordType === "category" ? 0 : 1}
                tabType={TabsTypes.BROWSE}
                largeTabs
                extendContainer
                includeBorder={false}
                includeVerticalPadding={false}
                extraButtons={<EventFilter filter={filter} onFilterChange={changeFilter} />}
                onChange={newTab => {
                    const newSearch = new URLSearchParams(location.search);
                    newSearch.set("page", "1");
                    const newLocation: LocationDescriptorObject = {
                        ...location,
                        pathname: getEventPath(`/${newTab.parentRecordType}`),
                        search: newSearch.toString(),
                    };

                    history.push(newLocation);
                }}
                data={[
                    {
                        parentRecordType: "category",
                        label: t("Community Events"),
                        contents: (
                            <>
                                <EventsHomePageTab parentRecordType={"category"} page={page} filterType={filter} />
                            </>
                        ),
                    },
                    {
                        parentRecordType: "group",
                        label: t("Group Events"),
                        contents: (
                            <>
                                <EventsHomePageTab parentRecordType={"group"} page={page} filterType={filter} />
                            </>
                        ),
                    },
                ]}
            />
        </div>
    );
}
