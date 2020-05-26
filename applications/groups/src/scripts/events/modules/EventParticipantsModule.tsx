import React, { useState, useEffect } from "react";
import EventParticipants from "@groups/events/ui/EventParticipants";
import { useEventParticipants, useEventParticipantsByAttendance } from "../state/eventsHooks";
import { LoadStatus } from "@vanilla/library/src/scripts/@types/api/core";
import Loader from "@vanilla/library/src/scripts/loaders/Loader";
import ErrorMessages from "@vanilla/library/src/scripts/forms/ErrorMessages";
import { notEmpty } from "@vanilla/utils";
import { IGetEventParticipantsQuery, useEventsActions, EventsActions } from "../state/EventsActions";
import { EventAttendance } from "../state/eventsTypes";
import EventParticipantsTabs from "@groups/events/ui/EventParticipantsTabs";

interface IParticipantsProps {
    query: IGetEventParticipantsQuery;
}

interface IProps {
    eventID: number;
}

export function EventParticipantsModule(props: IParticipantsProps) {
    const participants = useEventParticipants(props.query);

    if ([LoadStatus.PENDING, LoadStatus.LOADING].includes(participants.status) && !participants.data) {
        return <Loader />;
    }

    if (!participants.data || participants.error) {
        return <ErrorMessages errors={[participants.error].filter(notEmpty)} />;
    }

    return <EventParticipants participants={participants.data.participants} />;
}

export function EventParticipantsByAttendanceModule(props: IProps) {
    const { eventID } = props;
    const { getEventParticipantsByAttendance } = useEventsActions();

    const [yesPage, setYesPage] = useState(1);
    const [maybePage, setMaybePage] = useState(1);
    const [noPage, setNoPage] = useState(1);

    const yesQuery = {
        eventID,
        yesPage,
        limit: EventsActions.DEFAULT_PARTICIPANTS_LIMIT,
        attending: EventAttendance.GOING,
    };
    const maybeQuery = {
        eventID,
        maybePage,
        limit: EventsActions.DEFAULT_PARTICIPANTS_LIMIT,
        attending: EventAttendance.MAYBE,
    };
    const noQuery = {
        eventID,
        noPage,
        limit: EventsActions.DEFAULT_PARTICIPANTS_LIMIT,
        attending: EventAttendance.NOT_GOING,
    };

    const yesParticipants = useEventParticipantsByAttendance(yesQuery);
    const maybeParticipants = useEventParticipantsByAttendance(maybeQuery);
    const noParticipants = useEventParticipantsByAttendance(noQuery);

    const [tabIndex, setTabIndex] = useState(0);
    const handleTabsChange = (index: number) => {
        setTabIndex(index);
    };

    const loadMore = () => {
        let page;
        switch (tabIndex) {
            case 0:
                page = yesPage + 1;
                setYesPage(page);
                getEventParticipantsByAttendance({ ...yesQuery, page });
                break;
            case 1:
                page = maybePage + 1;
                setMaybePage(page);
                getEventParticipantsByAttendance({ ...maybeQuery, page });
                break;
            case 2:
                page = noPage + 1;
                setNoPage(page);
                getEventParticipantsByAttendance({ ...noQuery, page });
                break;
        }
    };

    if ([LoadStatus.PENDING, LoadStatus.LOADING].includes(yesParticipants.status) && !yesParticipants.data) {
        return <Loader />;
    }

    if (!yesParticipants.data || yesParticipants.error) {
        return <ErrorMessages errors={[yesParticipants.error].filter(notEmpty)} />;
    }

    if ([LoadStatus.PENDING, LoadStatus.LOADING].includes(maybeParticipants.status) && !maybeParticipants.data) {
        return <Loader />;
    }

    if (!maybeParticipants.data || maybeParticipants.error) {
        return <ErrorMessages errors={[maybeParticipants.error].filter(notEmpty)} />;
    }

    if ([LoadStatus.PENDING, LoadStatus.LOADING].includes(noParticipants.status) && !noParticipants.data) {
        return <Loader />;
    }

    if (!noParticipants.data || noParticipants.error) {
        return <ErrorMessages errors={[noParticipants.error].filter(notEmpty)} />;
    }

    return (
        <EventParticipantsTabs
            yesParticipants={yesParticipants.data.participants}
            maybeParticipants={maybeParticipants.data.participants}
            noParticipants={noParticipants.data.participants}
            tabIndex={tabIndex}
            handleTabsChange={handleTabsChange}
            closeClick={() => alert("close")}
            loadMore={loadMore}
        />
    );
}
