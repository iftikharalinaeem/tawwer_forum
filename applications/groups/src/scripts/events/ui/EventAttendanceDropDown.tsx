/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import { t } from "@library/utility/appUtils";
import SelectBox, { ISelectBoxItem } from "@library/forms/select/SelectBox";
import React from "react";
import { eventsClasses } from "@groups/events/ui/eventStyles";
import { EventAttendance } from "@groups/events/ui/eventOptions";

export interface IEventAttendance {
    attendance: EventAttendance;
    options: ISelectBoxItem[];
}

/**
 * Component for displaying/selecting attendance to an event
 */
export default function EventAttendanceDropDown(props: IEventAttendance) {
    if (props.options.length === 0) {
        return null;
    }

    const activeOption = props.options.find(option => option.value === props.attendance);

    return (
        <>
            <SelectBox
                className={eventsClasses().dropDown}
                widthOfParent={false}
                options={props.options}
                label={t("Will you be attending?")}
                value={
                    activeOption ?? {
                        name: t("RSVP"),
                        value: EventAttendance.RSVP,
                    }
                }
                renderLeft={true}
                offsetPadding={true}
            />
        </>
    );
}
