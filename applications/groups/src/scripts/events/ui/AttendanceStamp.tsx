/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { eventsClasses } from "@groups/events/ui/eventStyles";
import classNames from "classnames";
import { EventAttendance } from "@groups/events/state/eventsTypes";

interface IProps {
    attendance: EventAttendance | null;
    className?: string;
}

/**
 * Component for displaying your attendance to an event
 */
export function AttendanceStamp(props: IProps) {
    const classes = eventsClasses();
    const attendance = props.attendance ?? EventAttendance.RSVP;
    return (
        <div className={classNames(classes.attendanceStamp, classes.attendanceClass(attendance), props.className)}>
            {attendance}
        </div>
    );
}
