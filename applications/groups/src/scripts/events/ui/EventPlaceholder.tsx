/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IEvent, EventAttendance } from "@groups/events/state/eventsTypes";
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
import { DateTimeCompactPlaceholder } from "@vanilla/library/src/scripts/content/DateTimeCompactPlaceholder";
import { LoadingRectange, LoadingSpacer } from "@vanilla/library/src/scripts/loaders/LoadingRectangle";

interface IProps {
    compact?: boolean;
    showMetas?: boolean;
    className?: string;
}

/**
 * Component for displaying an event in a list
 */
export function EventPlaceholder(props: IProps) {
    const classes = eventsClasses();
    const { compact, showMetas } = props;

    const attendanceWidth = eventsVariables().spacing.attendanceOffset;
    const showAttendance = true;
    return (
        <li className={classNames(classes.item, props.className)}>
            <article className={classes.result}>
                <span
                    className={classes.link}
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
                        <DateTimeCompactPlaceholder className={classes.dateCompact} />
                        <div className={classes.main}>
                            <LoadingRectange height={16} width={120} />
                            <LoadingSpacer height={7} />
                            {!props.compact && <LoadingRectange height={10} width={240} />}
                            <div className={classes.metas}>
                                <LoadingRectange
                                    width={60}
                                    height={10}
                                    className={classes.meta}
                                    style={{ display: "inline-block" }}
                                />
                                <LoadingRectange
                                    width={32}
                                    height={10}
                                    className={classes.meta}
                                    style={{ display: "inline-block" }}
                                />
                                <LoadingRectange
                                    width={48}
                                    height={10}
                                    className={classes.meta}
                                    style={{ display: "inline-block" }}
                                />
                            </div>
                            {/* {showMetas && (

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
                            )} */}
                        </div>
                    </div>
                </span>
            </article>
        </li>
    );
}
