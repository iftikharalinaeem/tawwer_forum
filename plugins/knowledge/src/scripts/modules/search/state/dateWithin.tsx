/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import { t } from "@library/application";

export enum ISearchWithin {
    ONE_DAY = "1 day",
    THREE_DAYS = "3 days",
    ONE_WEEK = "1 week",
    TWO_WEEKS = "2 weeks",
    ONE_MONTH = "1 month",
    SIX_MONTHS = "6 months",
    ONE_YEAR = "1 year",
}

export const dateWithin = [
    {
        label: t(ISearchWithin.ONE_DAY),
        value: ISearchWithin.ONE_DAY,
        data: {
            within: ISearchWithin.ONE_DAY,
        },
    },
    {
        label: t(ISearchWithin.THREE_DAYS),
        value: ISearchWithin.THREE_DAYS,
        data: {
            within: ISearchWithin.THREE_DAYS,
        },
    },
    {
        label: t(ISearchWithin.ONE_WEEK),
        value: ISearchWithin.ONE_WEEK,
        data: {
            within: ISearchWithin.ONE_WEEK,
        },
    },
    {
        label: t(ISearchWithin.TWO_WEEKS),
        value: ISearchWithin.TWO_WEEKS,
        data: {
            within: ISearchWithin.TWO_WEEKS,
        },
    },
    {
        label: t(ISearchWithin.ONE_MONTH),
        value: ISearchWithin.ONE_MONTH,
        data: {
            within: ISearchWithin.ONE_MONTH,
        },
    },
    {
        label: t(ISearchWithin.SIX_MONTHS),
        value: ISearchWithin.SIX_MONTHS,
        data: {
            within: ISearchWithin.SIX_MONTHS,
        },
    },
    {
        label: t(ISearchWithin.ONE_YEAR),
        value: ISearchWithin.ONE_YEAR,
        data: {
            within: ISearchWithin.ONE_YEAR,
        },
    },
];
