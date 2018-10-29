/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import {t} from "@library/application";
import {ISearchWithin} from "../components/AdvancedSearch";

export const dummyDateWithin = [
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
