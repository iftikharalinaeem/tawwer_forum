/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { StoryHeading } from "@library/storybook/StoryHeading";
import { layoutVariables } from "@library/layout/panelLayoutStyles";
import { EventDetails, IEventExtended } from "@groups/events/ui/EventDetails";
import { dummyEventDetailsData } from "@library/dataLists/dummyEventData";

export default {
    title: "Event Details",
    parameters: {
        chromatic: {
            viewports: [1450, layoutVariables().panelLayoutBreakPoints.xs],
        },
    },
};

export function StoryEventDetails(props: { data: IEventExtended; title: string }) {
    const { data = dummyEventDetailsData, title = "Event Details" } = props;
    return (
        <>
            <StoryHeading depth={1}>{title}</StoryHeading>
            <EventDetails {...data} />
        </>
    );
}
