/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useEffect, useState } from "react";
import { LoadStatus } from "@library/@types/api/core";
import { useParams } from "react-router";
import { useEventActions } from "@groups/events/state/EventActions";
import { useEventState } from "@groups/events/state/EventReducer";
import Loader from "@library/loaders/Loader";
import { IUserFragment } from "@library/@types/api/users";
import { Devices, useDevice } from "@library/layout/DeviceContext";
import { panelBackgroundVariables } from "@library/layout/panelBackgroundStyles";
import Container from "@library/layout/components/Container";
import PanelLayout, { PanelWidget } from "@library/layout/PanelLayout";
import Breadcrumbs from "@library/navigation/Breadcrumbs";
import { t } from "@library/utility/appUtils";
import PanelEmptyColumn from "@knowledge/modules/search/components/PanelEmptyColumn";
import { EventDetails } from "@groups/events/ui/EventDetails";
import TitleBar from "@vanilla/library/src/scripts/headers/TitleBar";
import PageTitle from "@knowledge/modules/common/PageTitle";
import { EventsOptionsDropDown } from "@groups/events/ui/EventsOptionsDropDown";

export default function EventPage() {
    const { getEventByID, getEventParticipantsByEventID, postEventParticipants } = useEventActions();
    const [disableAttendance, setDisableAttendance] = useState(false);

    let eventID = useParams<{
        id: string;
    }>().id;

    const device = useDevice();
    const renderPanelBackground =
        device !== Devices.MOBILE && device !== Devices.XS && panelBackgroundVariables().config.render;

    const eventState = useEventState();
    let event = eventState.event;
    let participants = eventState.eventParticipants;
    let postParticipants = eventState.participant;

    useEffect(() => {
        if (event.status === LoadStatus.PENDING || event.status === LoadStatus.LOADING) {
            getEventByID(parseInt(eventID));
            getEventParticipantsByEventID(parseInt(eventID));
        }
    }, [event.data]);

    const organizer = event.data?.insertUser;

    const crumbs = event.data?.breadcrumbs;
    const lastCrumb = crumbs && crumbs.length > 1 ? crumbs.slice(t.length - 1) : crumbs;

    const setAttendance = async attendingStatus => {
        setDisableAttendance(true);
        await postEventParticipants({ id: parseInt(eventID), attending: attendingStatus });
        let event = await getEventByID(parseInt(eventID));
        let eventParticipants = await getEventParticipantsByEventID(parseInt(eventID));
        if (event && eventParticipants) {
            setDisableAttendance(false);
        }
    };

    if (!event.data || !participants.data) {
        return <Loader />;
    } else {
        const going: IUserFragment[] = participants?.data
            .filter(participant => {
                if (participant.attending === "yes") {
                    return participant.user;
                }
            })
            .map(participant => {
                return participant.user;
            });
        const notGoing: IUserFragment[] = participants?.data
            .filter(participant => {
                if (participant.attending === "no") {
                    return participant.user;
                }
            })
            .map(participant => {
                return participant.user;
            });

        const maybe: IUserFragment[] = participants?.data
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
                <TitleBar />
                <PanelLayout
                    renderLeftPanelBackground={renderPanelBackground}
                    leftBottom={<></>}
                    breadcrumbs={
                        (device === Devices.XS || device === Devices.MOBILE) && crumbs
                            ? lastCrumb && <Breadcrumbs forceDisplay={false}>{lastCrumb}</Breadcrumbs>
                            : crumbs && <Breadcrumbs forceDisplay={false}>{crumbs}</Breadcrumbs>
                    }
                    middleTop={
                        <PageTitle
                            title={event.data.name}
                            actions={<EventsOptionsDropDown eventID={parseInt(eventID)} />}
                            includeBackLink={false}
                        />
                    }
                    middleBottom={
                        <PanelWidget>
                            <EventDetails
                                event={event.data}
                                organizer={organizer.name}
                                going={going}
                                notGoing={notGoing}
                                maybe={maybe}
                                onChange={setAttendance}
                                disableAttendance={disableAttendance}
                            />
                        </PanelWidget>
                    }
                    rightTop={<PanelEmptyColumn />}
                />
            </Container>
        );
    }
}
