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
import { FromToDateTime } from "@library/content/FromToDateTime";
import { t } from "@vanilla/i18n";
import { useUniqueID } from "@vanilla/library/src/scripts/utility/idUtils";

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
    const showAttendance = event.attending && event.attending !== EventAttendance.RSVP;
    const showMetas = event.location || !props.compact || showAttendance;

    const locationID = useUniqueID("eventLocation");

    return (
        <li className={classNames(classes.item, props.className)}>
            <article className={classes.result}>
                <div
                    className={classes.wrapper}
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
                        <div className={classes.main()}>
                            <SmartLink to={event.url} className={classes.link} tabIndex={0}>
                                <HeadingTag title={event.name} className={classes.title}>
                                    {event.name}
                                </HeadingTag>
                            </SmartLink>
                            {!props.compact && (
                                <Paragraph className={classes.excerpt}>
                                    <TruncatedText maxCharCount={160}>{event.excerpt}</TruncatedText>
                                </Paragraph>
                            )}
                            {showMetas && (
                                <div className={classes.metas}>
                                    {showAttendance && (
                                        <AttendanceStamp
                                            attending={event.attending}
                                            className={classNames(
                                                classes.meta,
                                                !props.compact && classes.metaAttendance,
                                            )}
                                        />
                                    )}
                                    {event.location && (
                                        <div className={classes.meta}>
                                            <label className={classes.metaLabel} htmlFor={locationID}>
                                                {t("Location")}:
                                            </label>
                                            <span id={locationID}>{event.location}</span>
                                        </div>
                                    )}
                                    {!props.compact && (
                                        <div className={classNames(classes.meta, classes.metaDate)}>
                                            <FromToDateTime
                                                dateStarts={event.dateStarts}
                                                dateEnds={event.allDayEvent ? undefined : event.dateEnds}
                                            />
                                        </div>
                                    )}
                                    {event.parentRecord && (
                                        <SmartLink className={classes.meta} to={event.parentRecord.url}>
                                            <strong>{event.parentRecord.name}</strong>
                                        </SmartLink>
                                    )}
                                </div>
                            )}
                        </div>
                    </div>
                </div>

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
