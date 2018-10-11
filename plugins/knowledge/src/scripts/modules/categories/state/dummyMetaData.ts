/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { KbCategoryDisplayType } from "@knowledge/@types/api";
import {InlineTypes} from "@library/components/Sentence";

export const dummyMetaData: any = {
    children: [
        {
            children: "By Todd Burry",
            type: InlineTypes.TEXT,
        },
        {
            children: [
                {
                    children: "Last Updated: ",
                    type: InlineTypes.TEXT,
                },
                {
                    timeStamp: "2018-03-03",
                    type: InlineTypes.DATETIME,
                    children: [
                        {
                            children: "3 March 2018",
                            type: InlineTypes.TEXT,
                        },
                    ],
                },
            ],
        },
        {
            children: "ID #1029384756",
            type: InlineTypes.TEXT,
        },
    ],
};
