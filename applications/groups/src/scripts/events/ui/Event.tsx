/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IEvent, EventAttendance, EventPermissionName } from "@groups/events/state/eventsTypes";
import { AttendanceStamp } from "@groups/events/ui/AttendanceStamp";
import { eventsClasses, eventsVariables } from "@groups/events/ui/eventStyles";
import DateTime, { DateFormats } from "@library/content/DateTime";
import TruncatedText from "@library/content/TruncatedText";
import Paragraph from "@library/layout/Paragraph";
import SmartLink from "@library/routing/links/SmartLink";
import { globalVariables } from "@library/styles/globalStyleVars";
import classNames from "classnames";
import { calc } from "csx";
import React from "react";
import { EventPermission } from "@groups/events/state/EventPermission";
import EventAttendanceDropDown from "@groups/events/ui/EventAttendanceDropDown";

interface IProps {
    event: IEvent;
    headingLevel?: 2 | 3 | 4;
    className?: string;
    compact?: boolean;
    longestCharCount?: number; // for dynamic width, based on language
}

/**
 * Component for displaying an event in a list
 */
export function Event(props: IProps) {
    const classes = eventsClasses();
    const { event } = props;

    const HeadingTag = (props.headingLevel ? `h${props.headingLevel}` : "h2") as "h2" | "h3";

    const attendanceWidth = `${eventsVariables().spacing.attendanceOffset + (props.longestCharCount || 0)}ex`;
    const showAttendance = props.compact && event.attending !== EventAttendance.RSVP;
    const showMetas = event.location || !props.compact || showAttendance;
    return (
        <li className={classNames(classes.item, props.className)}>
            <article className={classes.result}>
                <SmartLink
                    to={event.url}
                    className={classes.link}
                    tabIndex={0}
                    style={
                        showAttendance
                            ? {
                                  maxWidth: !props.compact ? calc(`100% - ${attendanceWidth}`) : undefined,
                                  fontSize: props.compact
                                      ? eventsVariables().attendanceStamp.font.size
                                      : globalVariables().fonts.size.medium, // Needed for correct ex calculation
                              }
                            : {}
                    }
                >
                    <div className={classes.linkAlignment}>
                        <DateTime
                            className={classes.dateCompact}
                            type={DateFormats.COMPACT}
                            timestamp={event.dateStarts}
                        />
                        <div className={classes.main}>
                            <HeadingTag title={event.name} className={classes.title}>
                                {event.name}
                            </HeadingTag>
                            {!props.compact && (
                                <Paragraph className={classes.excerpt}>
                                    <TruncatedText maxCharCount={160}>{event.excerpt}</TruncatedText>
                                </Paragraph>
                            )}
                            {showMetas && (
                                <div className={classes.metas}>
                                    {showAttendance && (
                                        <AttendanceStamp attendance={event.attending} className={classes.meta} />
                                    )}
                                    {event.location && <div className={classes.meta}>{event.location}</div>}
                                    {!props.compact && (
                                        <div className={classes.meta}>
                                            <DateTime type={DateFormats.DEFAULT} timestamp={event.dateStarts} />
                                        </div>
                                    )}
                                </div>
                            )}
                        </div>
                    </div>
                </SmartLink>
                <EventPermission permission={EventPermissionName.ATTEND} event={props.event}>
                    {!props.compact && (
                        <div
                            className={classes.attendance}
                            style={{
                                flexBasis: `${attendanceWidth}`,
                                width: `${attendanceWidth}`,
                            }}
                        >
                            <EventAttendanceDropDown event={props.event} />
                        </div>
                    )}
                </EventPermission>
            </article>
        </li>
    );
}
