/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import { AttachmentType } from "@knowledge/modules/common/AttachmentIcon";
import { IResult } from "@knowledge/modules/common/SearchResult";
import { t } from "@library/application";
import { dummyMetaData } from "@knowledge/modules/categories/state/dummyMetaData";
import { KbCategoryDisplayType } from "@knowledge/@types/api/kbCategory";

export const dummySearchResults: IResult[] = [
    {
        name: "Getting Help with your community",
        meta: dummyMetaData,
        url: "#",
        excerpt:
            "Standard with your order of the Plantronics CT12 wireless headset phone is a two in one headset that is convertible so you can use it over the head for stability or over the ear for convenience. It has a microphone that is especially designed to cancel out background noises as well as top notch clarity of sound.",
        image: "https://us.v-cdn.net/5022541/uploads/942/WKEOVS2LF32Y.png",
        attachments: [
            {
                name: "Some Word Document",
                type: AttachmentType.WORD,
            },
            {
                name: "Some Word Document 2",
                type: AttachmentType.FILE,
            },
            {
                name: "Some Word Document 3",
                type: AttachmentType.PDF,
            },
            {
                name: "Some Word Document 4",
                type: AttachmentType.EXCEL,
            },
            {
                name: "Some Word Document 5",
                type: AttachmentType.WORD,
            },
            {
                name: "Some Word Document 6",
                type: AttachmentType.EXCEL,
            },
            {
                name: "Some Word Document 7",
                type: AttachmentType.WORD,
            },
            {
                name: "Some Word Document 8",
                type: AttachmentType.WORD,
            },
            {
                name: "Some Word Document 9",
                type: AttachmentType.WORD,
            },
        ],
        dateUpdated: "2018-10-22T16:56:37.423Z",
        location: [
            {
                name: "Article",
                knowledgeCategoryID: 1,
                parentID: 1,
                displayType: KbCategoryDisplayType.HELP,
                isSection: false,
                url: "#",
            },
            {
                name: "Location",
                knowledgeCategoryID: 1,
                parentID: 1,
                displayType: KbCategoryDisplayType.HELP,
                isSection: false,
                url: "#",
            },
            {
                name: "Breadcrumb",
                knowledgeCategoryID: 1,
                parentID: 1,
                displayType: KbCategoryDisplayType.HELP,
                isSection: false,
                url: "#",
            },
        ],
    },
    {
        name: "Some Fake Search Results!",
        meta: dummyMetaData,
        url: "#",
        excerpt: "Standard with your order.",
        image: "https://library.vanillaforums.com/wp-content/uploads/2018/09/Case-study-headers-2018-1.png",
        attachments: [],
        dateUpdated: "2018-10-22T16:56:37.423Z",
        location: [t("Help & Training"), t("Getting Started")],
    },
    {
        name: "Beaver defeats Hyena; Hyena quits.",
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
        dateUpdated: "2018-10-22T16:56:37.423Z",
        location: [
            {
                name: "Help",
                knowledgeCategoryID: 1,
                parentID: 1,
                displayType: KbCategoryDisplayType.HELP,
                isSection: false,
                url: "#",
            },
            {
                name: "Getting Started",
                knowledgeCategoryID: 1,
                parentID: 1,
                displayType: KbCategoryDisplayType.HELP,
                isSection: false,
                url: "#",
            },
        ],
    },
    {
        name:
            "Getting Help with your communityGetting Help with your communityGetting Help with your communityGetting Help with your communityGetting Help with your communityGetting Help with your communityGetting Help with your communityGetting Help with your community",
        meta: dummyMetaData,
        url: "#",
        excerpt:
            "Standard with your order.Standard with your order.Standard with your order.Standard with your order.Standard with your order.Standard with your order.Standard with your order.Standard with your order.Standard with your order.Standard with your order.Standard with your order.Standard with your order.Standard with your order.Standard with your order.Standard with your order.Standard with your order.Standard with your order.Standard with your order.Standard with your order.Standard with your order.Standard with your order.Standard with your order.",
        attachments: [
            {
                name: "Some Word Document 1",
                type: AttachmentType.WORD,
            },
            {
                name: "Some Word Document 2",
                type: AttachmentType.PDF,
            },
            {
                name: "Some Word Document 3",
                type: AttachmentType.FILE,
            },
            {
                name: "Some Word Document 4",
                type: AttachmentType.EXCEL,
            },
        ],
        dateUpdated: "2018-10-22T16:56:37.423Z",
        location: [
            {
                name: "Help",
                knowledgeCategoryID: 1,
                parentID: 1,
                displayType: KbCategoryDisplayType.HELP,
                isSection: false,
                url: "#",
            },
            {
                name: "Getting Started",
                knowledgeCategoryID: 1,
                parentID: 1,
                displayType: KbCategoryDisplayType.HELP,
                isSection: false,
                url: "#",
            },
        ],
    },
];
