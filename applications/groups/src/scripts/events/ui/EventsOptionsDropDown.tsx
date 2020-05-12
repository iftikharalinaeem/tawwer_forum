import React, { useState } from "react";
import DropDown, { FlyoutType } from "@library/flyouts/DropDown";
import DropDownItem from "@library/flyouts/items/DropDownItem";
import SmartLink from "@library/routing/links/SmartLink";
import DropDownItemSeparator from "@library/flyouts/items/DropDownItemSeparator";
import ModalConfirm from "@library/modal/ModalConfirm";
import { t } from "@library/utility/appUtils";
import { LoadStatus } from "@library/@types/api/core";
import { useEventActions } from "@groups/events/state/EventActions";
import { useEventState } from "@groups/events/state/EventReducer";
import { buttonClasses } from "@library/forms/buttonStyles";
import classNames from "classnames";
import { dropDownClasses } from "@library/flyouts/dropDownStyles";
import DropDownItemButton from "@library/flyouts/items/DropDownItemButton";

interface IProps {
    eventID: number;
}

export const EventsOptionsDropDown = (props: IProps) => {
    const [visible, setVisible] = useState<boolean>(false);
    const [deleteID, setDeleteID] = useState<number | string | null>(props.eventID);

    const classesButtons = buttonClasses();
    const classesDropDown = dropDownClasses();

    const { deleteEvent } = useEventActions();
    const deleteStatus = useEventState();

    return (
        <>
            {deleteID !== null && (
                <ModalConfirm
                    isVisible={visible}
                    title={t("Delete")}
                    onCancel={e => {
                        setVisible(false);
                    }}
                    onConfirm={e => {
                        e.stopPropagation();
                        deleteEvent(props.eventID);
                    }}
                    isConfirmLoading={deleteStatus.deleteEvent.status === LoadStatus.LOADING}
                    elementToFocusOnExit={document.activeElement as HTMLElement}
                >
                    {t("Do you want to delete this event?")}
                </ModalConfirm>
            )}
            <DropDown flyoutType={FlyoutType.LIST} className={classNames("pageTitle-menu", classesButtons.icon)}>
                <DropDownItem>
                    <SmartLink to={`/event/edit/${props.eventID}`} className={classesDropDown.action}>
                        {t("Edit")}
                    </SmartLink>
                </DropDownItem>
                <DropDownItemSeparator />
                <DropDownItemButton onClick={e => setVisible(true)}>{t("Delete")}</DropDownItemButton>
            </DropDown>
        </>
    );
};
