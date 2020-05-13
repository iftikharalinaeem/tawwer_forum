/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { eventsClasses } from "@groups/events/ui/eventStyles";
import { EventPlaceholder } from "@groups/events/ui/EventPlaceholder";

interface IProps {
    count: number;
}

export function EventListPlaceholder(props: IProps) {
    const classes = eventsClasses();
    return (
        <div className={classes.list}>
            {Array.from(new Array(props.count ?? 5)).map((_, i) => {
                return <EventPlaceholder key={i} />;
            })}
        </div>
    );
}
