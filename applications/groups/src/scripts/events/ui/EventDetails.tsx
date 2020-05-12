/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { IUserFragment } from "@library/@types/api/users";
import { FromToDateTime } from "@library/content/FromToDateTime";
import UserContent from "@library/content/UserContent";
import { DataList } from "@library/dataLists/DataList";
import { EventAttendees } from "@groups/events/ui/Attendees";
import { eventsClasses } from "@groups/events/ui/eventStyles";
import ButtonTab from "@library/forms/buttonTabs/ButtonTab";
import { ButtonTabs } from "@library/forms/buttonTabs/ButtonTabs";
import { t } from "@vanilla/i18n/src";
import React from "react";
import { IEvent, EventAttendance } from "@groups/events/state/eventsTypes";
import SmartLink from "@vanilla/library/src/scripts/routing/links/SmartLink";
import { makeProfileUrl } from "@vanilla/library/src/scripts/utility/appUtils";

interface IProps {
    event: IEvent;
    organizer: string;
    going?: IUserFragment[];
    maybe?: IUserFragment[];
    notGoing?: IUserFragment[];
}

/**
 * Component for displaying an event details
 */
export function EventDetails(props: IProps) {
    const classes = eventsClasses();
    const { event } = props;

    return (
        <div className={classes.details}>
            <DataList
                data={[
                    {
                        key: t("When"),
                        value: <FromToDateTime dateStarts={event.dateStarts} dateEnds={event.dateEnds} />,
                    },
                    {
                        key: t("Where"),
                        value: event.location,
                    },
                    {
                        key: t("Organizer"),
                        value: (
                            <SmartLink to={makeProfileUrl(event.insertUser.name)}>{event.insertUser.name}</SmartLink>
                        ),
                    },
                ]}
                className={classes.section}
                caption={t("Event Details")}
            />
            <ButtonTabs
                activeTab={props.event.attending ?? EventAttendance.RSVP}
                accessibleTitle={t("Are you going?")}
                setData={(data: EventAttendance) => {
                    ///
                }}
                className={classes.attendanceSelector}
            >
                <ButtonTab label={t("Going")} data={EventAttendance.GOING.toString()} />
                <ButtonTab label={t("Maybe")} data={EventAttendance.MAYBE.toString()} />
                <ButtonTab label={t("Not going")} data={EventAttendance.NOT_GOING.toString()} className={"isLast"} />
            </ButtonTabs>

            <div className={classes.section}>
                <hr className={classes.separator} />
                <h2 className={classes.sectionTitle}>{t("About the event")}</h2>
                <UserContent className={classes.description} content={event.body} />
            </div>

            <EventAttendees
                data={props.going!}
                title={t("Going")}
                emptyMessage={t("Nobody has confirmed their attendance yet.")}
                extra={props.going?.length}
                separator={true}
            />
            <EventAttendees
                emptyMessage={t("Nobody is on the fence right now.")}
                data={props.maybe!}
                title={t("Maybe")}
                extra={props.maybe?.length}
                separator={true}
            />
            <EventAttendees
                emptyMessage={t("Nobody has declined the invitation so far.")}
                data={props.notGoing!}
                title={t("Not going")}
                extra={props.notGoing?.length}
                separator={true}
            />
        </div>
    );
}
