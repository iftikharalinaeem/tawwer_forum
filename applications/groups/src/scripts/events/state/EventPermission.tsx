/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { IEvent, EventPermissionName } from "@groups/events/state/eventsTypes";
import { logWarning } from "@vanilla/utils";
import { hasPermission } from "@library/features/users/Permission";

interface IProps {
    event: IEvent;
    permission: EventPermissionName;
    fallback?: React.ReactNode;
    children: React.ReactNode;
}

/**
 * Component for checking an event permission.
 *
 * Make sure the event you pass had it's permissions expanded from the API.
 */
export function EventPermission(props: IProps) {
    if (hasEventPermission(props.event, props.permission) || hasPermission("site.manage")) {
        return <>{props.children}</>;
    } else {
        return <>{props.fallback ?? null}</>;
    }
}

/**
 * Function for checking permissions on an event.
 * Make sure the event you pass had it's permissions expanded from the API.
 *
 * @param event The event.
 * @param permission The permission name.
 */
export function hasEventPermission(event: IEvent, permission: EventPermissionName): boolean {
    const { permissions } = event;
    if (!permissions) {
        logWarning(
            `Attempted to check event permission ${permission} on event ${event.eventID}, but the permissions were not fetched. Don't forget to expand permissions from the events API. False will be returned.`,
        );
        return false;
    }

    return permissions[permission];
}
