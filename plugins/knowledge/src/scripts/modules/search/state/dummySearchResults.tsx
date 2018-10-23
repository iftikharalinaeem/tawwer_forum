/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { AttachmentType } from "@knowledge/modules/common/AttachmentIcon";
import { IResult } from "@knowledge/modules/common/SearchResult";
import { dummyMetaData } from "@knowledge/modules/categories/state/dummyMetaData";

export const dummySearchResults: IResult[] = [
    {
        name: "Getting Help with your community",
        meta: dummyMetaData,
        url: "#",
        excerpt:
            "Standard with your order of the Plantronics CT12 wireless headset phone is a two in one headset that is convertible so you can use it over the head for stability or over the ear for convenience. It has a microphone that is especially designed to cancel out background noises as well as top notch clarity of sound.",
        image: "https://us.v-cdn.net/5022541/uploads/942/WKEOVS2LF32Y.png",
    },
    {
        name: "Getting Help with your community",
        meta: dummyMetaData,
        url: "#",
        excerpt: "Standard with your order.",
        image: "https://library.vanillaforums.com/wp-content/uploads/2018/09/Case-study-headers-2018-1.png",
    },
    {
        name: "Getting Help with your community",
        meta: dummyMetaData,
        url: "#",
        excerpt: "Standard with your order.",
        attachments: [
            {
                name: "Some Word Document 1",
                type: AttachmentType.EXCEL,
            },
        ],
        image: "https://library.vanillaforums.com/wp-content/uploads/2018/09/Case-study-headers-2018-1.png",
    },
    {
        name:
            "Getting Help with your communityGetting Help with your communityGetting Help with your communityGetting Help with your communityGetting Help with your communityGetting Help with your communityGetting Help with your communityGetting Help with your community",
        meta: dummyMetaData,
        url: "#",
        excerpt:
            "Standard with your order.Standard with your order.Standard with your order.Standard with your order.Standard with your order.Standard with your order.Standard with your order.Standard with your order.Standard with your order.Standard with your order.Standard with your order.Standard with your order.Standard with your order.Standard with your order.Standard with your order.Standard with your order.Standard with your order.Standard with your order.Standard with your order.Standard with your order.Standard with your order.Standard with your order.",
    },
];
