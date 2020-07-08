/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { useEvent, useEventAttendance } from "@groups/events/state/eventsHooks";
import { EventDetails } from "@groups/events/ui/EventDetails";
import { EventsOptionsDropDown } from "@groups/events/ui/EventsOptionsDropDown";
import { LoadStatus } from "@library/@types/api/core";
import Container from "@library/layout/components/Container";
import { panelBackgroundVariables } from "@library/layout/panelBackgroundStyles";
import PanelLayout from "@library/layout/PanelLayout";
import Loader from "@library/loaders/Loader";
import Breadcrumbs from "@library/navigation/Breadcrumbs";
import { t } from "@library/utility/appUtils";
import { ErrorPage } from "@vanilla/library/src/scripts/errorPages/ErrorComponent";
import TitleBar from "@vanilla/library/src/scripts/headers/TitleBar";
import React from "react";
import { useParams } from "react-router";
import { PageHeading } from "@library/layout/PageHeading";
import { useLayout } from "@library/layout/LayoutContext";
import PanelWidget from "@vanilla/library/src/scripts/layout/components/PanelWidget";
import ThreeColumnLayout from "@library/layout/ThreeColumnLayout";

export default function EventPage() {
    let eventID = useParams<{
        id: string;
    }>().id;

    const { isCompact } = useLayout();
    const renderPanelBackground = !isCompact && panelBackgroundVariables().config.render;

    const eventWithParticipants = useEvent(Number.parseInt(eventID, 10));
    const { setEventAttendance, setEventAttendanceLoadable } = useEventAttendance(Number.parseInt(eventID, 10));

    if ([LoadStatus.PENDING, LoadStatus.LOADING].includes(eventWithParticipants.status)) {
        return <Loader />;
    }

    if (!eventWithParticipants.data || eventWithParticipants.error) {
        return <ErrorPage error={eventWithParticipants.error} />;
    }

    const event = eventWithParticipants.data;

    let going = {
        users: event.attendingYesUsers,
        count: event.attendingYesCount,
    };
    let maybe = {
        users: event.attendingMaybeUsers,
        count: event.attendingMaybeCount,
    };
    let notGoing = {
        users: event.attendingNoUsers,
        count: event.attendingNoCount,
    };

    const organizer = event.insertUser;
    const crumbs = event.breadcrumbs;
    const lastCrumb = crumbs && crumbs.length > 1 ? crumbs.slice(t.length - 1) : crumbs;

    return (
        <Container>
            <TitleBar />
            <ThreeColumnLayout
                renderLeftPanelBackground={renderPanelBackground}
                leftBottom={<></>}
                breadcrumbs={
                    isCompact && crumbs
                        ? lastCrumb && <Breadcrumbs forceDisplay={false}>{lastCrumb}</Breadcrumbs>
                        : crumbs && <Breadcrumbs forceDisplay={false}>{crumbs}</Breadcrumbs>
                }
                middleTop={
                    <PanelWidget>
                        <PageHeading
                            title={event.name}
                            actions={<EventsOptionsDropDown event={event} />}
                            includeBackLink={false}
                        />
                    </PanelWidget>
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
            />
        </Container>
    );
}
