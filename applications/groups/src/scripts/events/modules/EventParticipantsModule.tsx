/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import EventParticipants from "@groups/events/ui/EventParticipants";
import { LoadStatus } from "@vanilla/library/src/scripts/@types/api/core";
import ErrorMessages from "@vanilla/library/src/scripts/forms/ErrorMessages";
import Loader from "@vanilla/library/src/scripts/loaders/Loader";
import { notEmpty } from "@vanilla/utils";
import React from "react";
import { EventsActions, useEventsActions } from "../state/EventsActions";
import { useEventParticipantsByAttendance } from "../state/eventsHooks";
import { EventAttendance } from "../state/eventsTypes";

interface IProps {
    eventID: number;
    attendanceStatus: EventAttendance;
    page: number;
    setPage: (page: number) => void;
}

export function EventParticipantsModule(props: IProps) {
    const { eventID, page, setPage } = props;
    const { getEventParticipantsByAttendance } = useEventsActions();

    const query = {
        eventID,
        page,
        limit: EventsActions.DEFAULT_PARTICIPANTS_LIMIT,
        attending: props.attendanceStatus,
    };

    const participants = useEventParticipantsByAttendance(query);

    const loadMore = () => {
        setPage(page + 1);
        getEventParticipantsByAttendance({ ...query, page: page + 1 });
    };

    if ([LoadStatus.PENDING, LoadStatus.LOADING].includes(participants.status) && !participants.data) {
        return <Loader />;
    }

    if (!participants.data || participants.error) {
        return <ErrorMessages errors={[participants.error].filter(notEmpty)} />;
    }

    return (
        <>
            <EventParticipants
                showLoadMore={participants.data.pagination.next}
                loadMore={loadMore}
                participants={participants.data.participants}
            />
        </>
    );
}
