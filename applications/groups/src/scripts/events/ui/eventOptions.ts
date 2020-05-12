/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import { ISelectBoxItem } from "@library/forms/select/SelectBox";
import { t } from "@vanilla/i18n/src";

export enum EventAttendance {
    RSVP = "rsvp", // only for default value in EventAttendanceDropDown
    GOING = "yes",
    MAYBE = "maybe",
    NOT_GOING = "no",
}

export const eventAttendanceOptions: ISelectBoxItem[] = [
    { name: t("Going"), value: EventAttendance.GOING },
    { name: t("Maybe"), value: EventAttendance.MAYBE },
    { name: t("Not going"), value: EventAttendance.NOT_GOING },
];
