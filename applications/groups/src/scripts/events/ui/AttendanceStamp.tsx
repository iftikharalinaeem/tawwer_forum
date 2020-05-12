/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { eventsClasses } from "@groups/events/ui/eventStyles";
import classNames from "classnames";
import { EventAttendance } from "@groups/events/ui/eventOptions";

/**
 * Component for displaying your attendance to an event
 */
export function AttendanceStamp(props: { attendance?: EventAttendance; className?: string }) {
    const classes = eventsClasses();
    if (!props.attendance) {
        return null;
    }
    return (
        <div
            className={classNames(classes.attendanceStamp, classes.attendanceClass(props.attendance), props.className)}
        >
            {props.attendance}
        </div>
    );
}
