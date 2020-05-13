/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import { ISelectBoxItem } from "@library/forms/select/SelectBox";
import { t } from "@vanilla/i18n/src";
import { EventAttendance } from "@groups/events/state/eventsTypes";

export const eventAttendanceOptions: ISelectBoxItem[] = [
    { name: t("Going"), value: EventAttendance.GOING },
    { name: t("Maybe"), value: EventAttendance.MAYBE },
    { name: t("Not going"), value: EventAttendance.NOT_GOING },
];
