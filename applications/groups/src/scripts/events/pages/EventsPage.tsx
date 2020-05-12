/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useEffect } from "react";
import { useEventsActions } from "@groups/eventList/state/EventsActions";
import { LoadStatus } from "@library/@types/api/core";
import { useParams, useLocation } from "react-router";
import { useEventsList, useEventParentRecord } from "@groups/events/state/eventsHooks";
import { EventsModule } from "@groups/events/modules/EventsModule";
import { ErrorPage } from "@vanilla/library/src/scripts/errorPages/ErrorComponent";
import { DefaultError } from "@vanilla/library/src/scripts/errorPages/CoreErrorMessages";
import Loader from "@vanilla/library/src/scripts/loaders/Loader";
import { LoadingRectange, LoadingSpacer } from "@vanilla/library/src/scripts/loaders/LoadingRectangle";
import ErrorMessages from "@vanilla/library/src/scripts/forms/ErrorMessages";
import { notEmpty } from "@vanilla/utils";
import PanelLayout from "@vanilla/library/src/scripts/layout/PanelLayout";
import Breadcrumbs from "@vanilla/library/src/scripts/navigation/Breadcrumbs";

export default function EventsPage() {
    const query = useQuery();
    const parentRecordType = query.get("parentRecordType") ?? "category";
    const parentRecordID = query.get("parentRecordID") !== null ? parseInt(query.get("parentRecordID")!) : -1;

    const isValidID = Number.isNaN(parentRecordID);

    const eventList = useEventsList({ parentRecordType, parentRecordID });
    const eventParent = useEventParentRecord({ parentRecordType, parentRecordID });

    if (
        [LoadStatus.PENDING, LoadStatus.LOADING].includes(eventList.status) ||
        [LoadStatus.PENDING, LoadStatus.LOADING].includes(eventParent.status)
    ) {
        return (
            <div>
                <LoadingRectange height={12} />
                <LoadingSpacer height={6} />
                <LoadingRectange height={12} />
            </div>
        );
    }

    if (!eventList.data || !eventParent.data || eventList.error || eventParent.error) {
        return <ErrorMessages errors={[eventList.error, eventParent.error].filter(notEmpty)} />;
    }

    return (
        <>
            <EventsModule query={{ parentRecordID, parentRecordType }} />
        </>
    );
}

// A custom hook that builds on useLocation to parse
// the query string for you.
function useQuery() {
    return new URLSearchParams(useLocation().search);
}
