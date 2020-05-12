/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { useEvent, useEventAttendance } from "@groups/events/state/eventsHooks";
import { EventDetails } from "@groups/events/ui/EventDetails";
import { EventsOptionsDropDown } from "@groups/events/ui/EventsOptionsDropDown";
import PageTitle from "@knowledge/modules/common/PageTitle";
import PanelEmptyColumn from "@knowledge/modules/search/components/PanelEmptyColumn";
import { LoadStatus } from "@library/@types/api/core";
import { IUserFragment } from "@library/@types/api/users";
import Container from "@library/layout/components/Container";
import { Devices, useDevice } from "@library/layout/DeviceContext";
import { panelBackgroundVariables } from "@library/layout/panelBackgroundStyles";
import PanelLayout, { PanelWidget } from "@library/layout/PanelLayout";
import Loader from "@library/loaders/Loader";
import Breadcrumbs from "@library/navigation/Breadcrumbs";
import { t } from "@library/utility/appUtils";
import { ErrorPage } from "@vanilla/library/src/scripts/errorPages/ErrorComponent";
import TitleBar from "@vanilla/library/src/scripts/headers/TitleBar";
import { notEmpty } from "@vanilla/utils";
import React from "react";
import { useParams } from "react-router";

export default function EventPage() {
    let eventID = useParams<{
        id: string;
    }>().id;

    const device = useDevice();
    const renderPanelBackground =
        device !== Devices.MOBILE && device !== Devices.XS && panelBackgroundVariables().config.render;

    const eventWithParticipants = useEvent(Number.parseInt(eventID, 10));
    const { setEventAttendance, setEventAttendanceLoadable } = useEventAttendance(Number.parseInt(eventID, 10));

    if ([LoadStatus.PENDING, LoadStatus.LOADING].includes(eventWithParticipants.status)) {
        return <Loader />;
    }

    if (!eventWithParticipants.data || eventWithParticipants.error) {
        return <ErrorPage error={eventWithParticipants.error} />;
    }

    let { participants, event } = eventWithParticipants.data;

    const organizer = event.insertUser;
    const crumbs = event.breadcrumbs;
    const lastCrumb = crumbs && crumbs.length > 1 ? crumbs.slice(t.length - 1) : crumbs;

    const going: IUserFragment[] = participants
        .filter(participant => {
            if (participant.attending === "yes") {
                return participant.user;
            }
        })
        .map(participant => {
            return participant.user;
        })
        .filter(notEmpty);
    const notGoing: IUserFragment[] = participants
        .filter(participant => {
            if (participant.attending === "no") {
                return participant.user;
            }
        })
        .map(participant => {
            return participant.user;
        })
        .filter(notEmpty);
    const maybe: IUserFragment[] = participants
        .filter(participant => {
            if (participant.attending === "maybe") {
                return participant.user;
            }
        })
        .map(participant => {
            return participant.user;
        })
        .filter(notEmpty);

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
                        title={event.name}
                        actions={<EventsOptionsDropDown event={event} />}
                        includeBackLink={false}
                    />
                }
                middleBottom={
                    <PanelWidget>
                        <EventDetails
                            event={event}
                            organizer={organizer.name}
                            going={going}
                            notGoing={notGoing}
                            maybe={maybe}
                            onChange={newAttending => {
                                setEventAttendance(newAttending);
                            }}
                            loadingAttendance={
                                setEventAttendanceLoadable.status === LoadStatus.LOADING
                                    ? setEventAttendanceLoadable.data?.attending
                                    : undefined
                            }
                        />
                    </PanelWidget>
                }
                rightTop={<PanelEmptyColumn />}
            />
        </Container>
    );
}
