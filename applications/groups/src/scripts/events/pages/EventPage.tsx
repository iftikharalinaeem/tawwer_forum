/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useEffect } from "react";
import { LoadStatus } from "@library/@types/api/core";
import { useParams } from "react-router";
import { useEventActions } from "@groups/events/state/EventActions";
import { IEventParticipant, useEventState } from "@groups/events/state/EventReducer";
import { EventDetails } from "@library/events/EventDetails";
import Loader from "@library/loaders/Loader";
import { eventAttendanceOptions } from "@library/events/eventOptions";
import { IUserFragment } from "@library/@types/api/users";
import { Devices, useDevice } from "@library/layout/DeviceContext";
import { panelBackgroundVariables } from "@library/layout/panelBackgroundStyles";
import Container from "@library/layout/components/Container";
import PanelLayout, { PanelWidget } from "@library/layout/PanelLayout";
import Breadcrumbs from "@library/navigation/Breadcrumbs";
import { t } from "@library/utility/appUtils";
import PanelEmptyColumn from "@knowledge/modules/search/components/PanelEmptyColumn";

export default function EventPage() {
    const { getEventByID, getEventParticipantsByEventID, postEventParticipants } = useEventActions();

    let eventID = useParams<{
        id: string;
    }>().id;

    const device = useDevice();
    const renderPanelBackground =
        device !== Devices.MOBILE && device !== Devices.XS && panelBackgroundVariables().config.render;

    const eventState = useEventState();
    let event = eventState.event;
    let participants = eventState.eventParticipants;

    useEffect(() => {
        if (event.status === LoadStatus.PENDING || event.status === LoadStatus.LOADING) {
            getEventByID(parseInt(eventID));
            getEventParticipantsByEventID(parseInt(eventID));
        }
    }, [event.data]);

    const eventCreator = event.data?.insertUser;
    const dateStarts = event.data?.dateStarts;
    const dateEnds = event.data?.dateEnds;
    const name = event.data?.name;
    const location = event.data?.location;
    const url = event.data?.url;
    const attendance = event.data?.attending;

    const crumbs = event.data?.breadcrumbs;
    const lastCrumb = crumbs && crumbs.length > 1 ? crumbs.slice(t.length - 1) : crumbs;

    if (
        !event.data ||
        !participants.data ||
        event.status === LoadStatus.LOADING ||
        event.status === LoadStatus.PENDING
    ) {
        return <Loader />;
    } else {
        const going: IUserFragment = participants?.data
            .filter(participant => {
                if (participant.attending === "yes") {
                    return participant.user;
                }
            })
            .map(participant => {
                return participant.user;
            });
        const notGoing: IUserFragment = participants?.data
            .filter(participant => {
                if (participant.attending === "no") {
                    return participant.user;
                }
            })
            .map(participant => {
                return participant.user;
            });

        const maybe: IUserFragment = participants?.data
            .filter(participant => {
                if (participant.attending === "maybe") {
                    return participant.user;
                }
            })
            .map(participant => {
                return participant.user;
            });

        return (
            <Container>
                <PanelLayout
                    renderLeftPanelBackground={renderPanelBackground}
                    breadcrumbs={
                        (device === Devices.XS || device === Devices.MOBILE) && crumbs
                            ? lastCrumb && <Breadcrumbs forceDisplay={false}>{lastCrumb}</Breadcrumbs>
                            : crumbs && <Breadcrumbs forceDisplay={false}>{crumbs}</Breadcrumbs>
                    }
                    middleBottom={
                        <PanelWidget>
                            <EventDetails
                                organizer={eventCreator.name}
                                dateStart={dateStarts}
                                dateEnd={dateEnds}
                                name={name}
                                location={location}
                                url={url}
                                attendance={attendance}
                                attendanceOptions={eventAttendanceOptions}
                                going={going}
                                notGoing={notGoing}
                                maybe={maybe}
                            />
                        </PanelWidget>
                    }
                    rightTop={<PanelEmptyColumn />}
                />
            </Container>
        );
    }
}
