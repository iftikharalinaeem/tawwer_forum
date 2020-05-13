/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { useState, useEffect } from "react";
import DropDown, { FlyoutType } from "@library/flyouts/DropDown";
import DropDownItem from "@library/flyouts/items/DropDownItem";
import SmartLink from "@library/routing/links/SmartLink";
import DropDownItemSeparator from "@library/flyouts/items/DropDownItemSeparator";
import ModalConfirm from "@library/modal/ModalConfirm";
import { t, formatUrl } from "@library/utility/appUtils";
import { LoadStatus } from "@library/@types/api/core";
import { buttonClasses } from "@library/forms/buttonStyles";
import classNames from "classnames";
import { dropDownClasses } from "@library/flyouts/dropDownStyles";
import DropDownItemButton from "@library/flyouts/items/DropDownItemButton";
import { useEventsActions } from "@groups/events/state/EventsActions";
import { useEventsState } from "@groups/events/state/eventsHooks";
import { IEvent, EventPermissionName } from "@groups/events/state/eventsTypes";
import Message from "@vanilla/library/src/scripts/messages/Message";
import { EventPermission, hasEventPermission } from "@groups/events/state/EventPermission";

interface IProps {
    event: IEvent;
}

export const EventsOptionsDropDown = (props: IProps) => {
    const { event } = props;
    const { eventID, breadcrumbs } = event;
    const [visible, setVisible] = useState<boolean>(false);

    const classesButtons = buttonClasses();
    const classesDropDown = dropDownClasses();

    const { deleteEvent, clearDeleteStatus } = useEventsActions();
    const deleteStatus = useEventsState().deleteStatusesByID[eventID] ?? {
        status: LoadStatus.PENDING,
    };

    const deleteRedirectUrl = breadcrumbs[breadcrumbs.length - 2]?.url ?? formatUrl("/");

    useEffect(() => {
        if (deleteStatus.status === LoadStatus.SUCCESS) {
            window.location.href = deleteRedirectUrl;
        }

        if (deleteStatus.status === LoadStatus.ERROR) {
            setVisible(false);
        }
    }, [deleteStatus, deleteRedirectUrl]);

    if (
        !hasEventPermission(event, EventPermissionName.EDIT) &&
        !hasEventPermission(event, EventPermissionName.ORGANIZER)
    ) {
        return null;
    }

    return (
        <>
            {deleteStatus.error && (
                <Message
                    isFixed
                    stringContents={deleteStatus.error.message}
                    onConfirm={() => {
                        clearDeleteStatus({ eventID });
                    }}
                />
            )}
            {visible && (
                <ModalConfirm
                    isVisible={visible}
                    title={t("Delete")}
                    onCancel={e => {
                        setVisible(false);
                    }}
                    onConfirm={async e => {
                        e.stopPropagation();
                        deleteEvent(eventID);
                    }}
                    isConfirmLoading={deleteStatus?.status === LoadStatus.LOADING}
                    elementToFocusOnExit={document.activeElement as HTMLElement}
                >
                    {t("Do you want to delete this event?")}
                </ModalConfirm>
            )}
            <DropDown flyoutType={FlyoutType.LIST} className={classNames("pageTitle-menu", classesButtons.icon)}>
                <EventPermission event={event} permission={EventPermissionName.EDIT}>
                    <DropDownItem>
                        <SmartLink to={`/event/edit/${eventID}`} className={classesDropDown.action}>
                            {t("Edit")}
                        </SmartLink>
                    </DropDownItem>
                    <DropDownItemSeparator />
                </EventPermission>
                <EventPermission event={event} permission={EventPermissionName.ORGANIZER}>
                    <DropDownItemButton onClick={e => setVisible(true)}>{t("Delete")}</DropDownItemButton>
                </EventPermission>
            </DropDown>
        </>
    );
};
