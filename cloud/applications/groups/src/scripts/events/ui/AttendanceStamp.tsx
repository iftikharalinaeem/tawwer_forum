/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { eventsClasses } from "@groups/events/ui/eventStyles";
import classNames from "classnames";
import { EventAttendance } from "@groups/events/state/eventsTypes";
import { t } from "@vanilla/library/src/scripts/utility/appUtils";

interface IProps {
    attending: EventAttendance | null;
    className?: string;
}

/**
 * Component for displaying your attendance to an event
 */
export function AttendanceStamp(props: IProps) {
    const classes = eventsClasses();
    const attending = props.attending ?? EventAttendance.RSVP;

    let text = t("RSVP");
    switch (attending) {
        case EventAttendance.GOING:
            text = t("Going");
            break;
        case EventAttendance.NOT_GOING:
            text = t("Not going");
            break;
        case EventAttendance.MAYBE:
            text = t("Maybe");
            break;
    }
    return (
        <div className={classNames(classes.attendanceStamp, classes.attendanceClass(attending), props.className)}>
            {text}
        </div>
    );
}
