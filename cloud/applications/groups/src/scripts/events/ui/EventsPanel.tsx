/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import SmartLink from "@library/routing/links/SmartLink";
import { t } from "@vanilla/i18n/src";
import { eventsClasses } from "@groups/events/ui/eventStyles";
import Heading from "@library/layout/Heading";
import { EventList, IEventList } from "@groups/events/ui/EventList";
import { IEvent } from "@groups/events/state/eventsTypes";
import { getMeta } from "@vanilla/library/src/scripts/utility/appUtils";

export interface IProps {
    events: IEvent[];
    viewMoreLink: string;
    viewMoreText?: string;
    title?: string;
    headingLevel?: 2 | 3;
}

/**
 * Component for displaying an event in a panel
 */
export function EventsPanel(props: IProps) {
    const {
        events,
        viewMoreLink,
        viewMoreText = t("More Events"),
        title = t("Upcoming Events"),
        headingLevel = 2,
    } = props;
    if (events.length === 0) {
        return null;
    }
    const classes = eventsClasses();
    const hasThemeFeature = getMeta("themeFeatures.NewEventsPage");
    return (
        <>
            <Heading className={classes.panelHeading}>{title}</Heading>
            <EventList events={events} compact={true} headingLevel={(headingLevel + 1) as 3 | 4} />
            {/* We don't actually have a functioning view more page unless the theme feature is enabled. */}
            {hasThemeFeature && (
                <SmartLink to={viewMoreLink} className={classes.viewMore}>
                    {viewMoreText}
                </SmartLink>
            )}
        </>
    );
}
