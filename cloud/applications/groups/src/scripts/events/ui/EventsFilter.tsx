/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { useMemo } from "react";
import { t } from "@vanilla/i18n/src";
import { eventsClasses } from "@groups/events/ui/eventStyles";
import SelectBox, { ISelectBoxItem } from "@library/forms/select/SelectBox";
import { uniqueIDFromPrefix } from "@library/utility/idUtils";
import { IGetEventsQuery } from "@groups/events/state/EventsActions";
import classNames from "classnames";
import { ButtonTypes } from "@vanilla/library/src/scripts/forms/buttonTypes";
import { EventAttendance } from "@groups/events/state/eventsTypes";

export enum EventFilterTypes {
    ALL = "all",
    UPCOMING = "upcoming",
    PAST = "past",
    MINE = "mine",
}

export function useEventQueryForFilter(filter: EventFilterTypes): Partial<IGetEventsQuery> {
    const dateNow = useMemo(() => {
        return new Date().toISOString();
    }, []);
    switch (filter) {
        case EventFilterTypes.ALL:
            return {
                sort: "-dateStarts",
            };
        case EventFilterTypes.PAST:
            return {
                dateStarts: `<${dateNow}`,
                sort: "-dateEnds",
            };
        case EventFilterTypes.UPCOMING:
            return {
                dateEnds: `>${dateNow}`,
                sort: "dateStarts",
            };
        case EventFilterTypes.MINE:
            return {
                attendingStatus: [EventAttendance.GOING, EventAttendance.MAYBE],
                dateEnds: `>${dateNow}`,
                sort: "dateStarts",
            };
        default:
            return {
                attendingStatus: [EventAttendance.GOING],
                dateEnds: `>${dateNow}`,
                sort: "dateStarts",
            };
    }
}

interface IProps {
    filter: EventFilterTypes;
    onFilterChange: (newFilter: EventFilterTypes) => void;
    className?: string;
}

/**
 * Component for displaying an event filters
 */
export default function EventFilter(props: IProps) {
    const { filter } = props;

    const options: ISelectBoxItem[] = [
        { name: t("All"), value: EventFilterTypes.ALL },
        { name: t("Upcoming Events"), value: EventFilterTypes.UPCOMING },
        { name: t("Past Events"), value: EventFilterTypes.PAST },
        { name: t("My Events"), value: EventFilterTypes.MINE },
    ];

    const activeOption = options.find(option => option.value === filter);
    const id = uniqueIDFromPrefix("attendanceFilter");
    const classes = eventsClasses();
    return (
        <div className={classNames(classes.filterRoot, props.className)}>
            <span id={id} className={classes.filterLabel}>
                {t("View")}:
            </span>
            <SelectBox
                buttonBaseClass={ButtonTypes.TEXT_PRIMARY}
                className={eventsClasses().dropDown}
                widthOfParent={false}
                options={options}
                describedBy={id}
                value={activeOption}
                renderLeft={false}
                offsetPadding={true}
                onChange={value => {
                    props.onFilterChange(value.value as EventFilterTypes);
                }}
            />
        </div>
    );
}