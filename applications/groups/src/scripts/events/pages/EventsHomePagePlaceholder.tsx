/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { EventListPlaceholder } from "@groups/events/ui/EventListPlaceholder";
import { LoadingRectange, LoadingSpacer } from "@vanilla/library/src/scripts/loaders/LoadingRectangle";

export function EventsHomePagePlaceholder() {
    return (
        <div style={{ marginTop: 20 }}>
            <LoadingRectange height={20} width={60} />
            <LoadingSpacer width={6} height={14} />

            <div style={{ display: "flex", justifyContent: "flex-start" }}>
                <LoadingRectange height={25} width={150} style={{ marginRight: 44 }} />
                <LoadingRectange height={25} width={120} />
                <LoadingRectange height={20} width={75} style={{ marginLeft: "auto" }} />
            </div>
            <LoadingSpacer height={20} />
            <EventListPlaceholder count={5} />
        </div>
    );
}
