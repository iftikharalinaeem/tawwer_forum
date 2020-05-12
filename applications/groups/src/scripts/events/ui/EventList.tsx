/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { eventsClasses } from "@groups/events/ui/eventStyles";
import { Event } from "@groups/events/ui/Event";
import classNames from "classnames";
import { t } from "@vanilla/i18n";
import { eventAttendanceOptions } from "@groups/events/ui/eventOptions";
import { IEvent } from "@groups/events/state/eventsTypes";

export interface IEventList {
    headingLevel?: 2 | 3 | 4;
    events: IEvent[];
    hideIfEmpty?: boolean;
    emptyMessage?: string;
    compact?: boolean;
}

/**
 * Component for displaying the list of events
 */
export function EventList(props: IEventList) {
    const classes = eventsClasses({
        compact: props.compact,
    });

    if (!props.events || props.events.length === 0) {
        const { hideIfEmpty = false, emptyMessage = t("This category does not have any events.") } = props;
        return hideIfEmpty ? null : <p className={classes.empty}>{emptyMessage}</p>;
    }
    const going = t("Going");
    const maybe = t("Maybe");

    let longestCharCount = 0;
    if (props.compact) {
        if (going.length > maybe.length) {
            longestCharCount = going.length;
        } else {
            longestCharCount = maybe.length;
        }
    } else {
        eventAttendanceOptions.forEach(o => {
            if (o.name && o.name.length > longestCharCount) {
                longestCharCount = o.name.length;
            }
        });
    }

    return (
        <>
            <ul className={classes.list}>
                {props.events.map((event, i) => {
                    return (
                        <Event
                            className={classNames({ isFirst: i === 0 })}
                            headingLevel={props.headingLevel}
                            event={event}
                            key={i}
                            longestCharCount={longestCharCount}
                            compact={props.compact}
                        />
                    );
                })}
            </ul>
        </>
    );
}
