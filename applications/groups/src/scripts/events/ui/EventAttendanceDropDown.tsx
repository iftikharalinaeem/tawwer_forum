/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import { t } from "@library/utility/appUtils";
import SelectBox, { ISelectBoxItem } from "@library/forms/select/SelectBox";
import React from "react";
import { eventsClasses } from "@groups/events/ui/eventStyles";
import { EventAttendance, IEvent } from "@groups/events/state/eventsTypes";
import { useEventAttendance } from "@groups/events/state/eventsHooks";
import { LoadStatus } from "@vanilla/library/src/scripts/@types/api/core";
import ButtonLoader from "@vanilla/library/src/scripts/loaders/ButtonLoader";
import { eventAttendanceOptions } from "@groups/events/ui/eventOptions";

interface IProps {
    event: IEvent;
    options?: ISelectBoxItem[];
}

/**
 * Component for displaying/selecting attendance to an event
 */
export default function EventAttendanceDropDown(props: IProps) {
    const { event, options = eventAttendanceOptions } = props;
    const { setEventAttendance, setEventAttendanceLoadable } = useEventAttendance(event.eventID);

    if (options.length === 0) {
        return null;
    }

    const value = setEventAttendanceLoadable.data?.attending ?? event.attending;

    let activeOption = options.find(option => option.value === value) ?? {
        name: t("RSVP"),
        value: EventAttendance.RSVP,
    };

    if (setEventAttendanceLoadable.status === LoadStatus.LOADING) {
        activeOption = {
            ...activeOption,
            content: (
                <>
                    {activeOption.name}
                    <ButtonLoader />
                </>
            ),
        };
    }

    return (
        <>
            <SelectBox
                className={eventsClasses().dropDown}
                widthOfParent={false}
                options={options}
                label={t("Will you be attending?")}
                value={activeOption}
                onChange={newOption => {
                    setEventAttendance(newOption.value as EventAttendance);
                }}
                renderLeft={true}
                offsetPadding={true}
            />
        </>
    );
}
