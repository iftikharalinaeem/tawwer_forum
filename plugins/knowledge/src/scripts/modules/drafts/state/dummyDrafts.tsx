/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import { AttachmentType } from "@library/components/attachments";
import { IResult } from "@knowledge/modules/common/SearchResult";
import { t } from "@library/application";
import { dummyMetaData } from "@knowledge/modules/categories/state/dummyMetaData";
import { IKbCategoryFragment } from "@knowledge/@types/api/kbCategory";
import { IDraftPreview } from "@knowledge/modules/drafts/components/DraftPreview";

export const dummyDraftListData: IDraftPreview[] = [
    {
        name: null,
        body: "Netus et malesuada fames ac turpis.",
        url: "#",
        dateUpdated: "2018-01-02T16:56:37.423Z",
        location: [
            {
                name: "Article",
                knowledgeCategoryID: 1,
                parentID: 1,
                url: "#",
            },
            {
                name: "Location",
                knowledgeCategoryID: 1,
                parentID: 1,
                url: "#",
            },
            {
                name: "Breadcrumb",
                knowledgeCategoryID: 1,
                parentID: 1,
                url: "#",
            },
        ],
    },
    {
        name: "Google OAuth",
        body: null,
        url: "#",
        dateUpdated: "2018-04-30T16:56:37.423Z",
    },
    {
        name: "Twitter SSO and Publishing",
        body:
            "Rig Veda shores of the cosmic ocean explorations of brilliant syntheses a still more glorious dawn awaits Vangelis. Are creatures of the cosmos gathered by gravity the only home we've ever known cosmic ocean Apollonius of Perga extraordinary claims require extraordinary evidence? Paroxysm of global death bits of moving fluff the carbon in our apple pies hundreds of thousands encyclopaedia galactica Sea of Tranquility. Courage of our questions two ghostly white figures in coveralls and helmets are soflty dancing Sea of Tranquility preserve and cherish that pale blue dot made in the interiors of collapsing stars two ghostly white figures in coveralls and helmets are soflty dancing and billions upon billions upon billions upon billions upon billions upon billions upon billions.",
        url: "#",
        dateUpdated: "2018-05-22T16:56:37.423Z",
    },
    {
        name: "Simple Authentication Markup Language, Simple Authentication Markup Language",
        body:
            "Accumsan tortor posuere ac ut consequat semperAccumsan tortor posuere ac ut consequat semperAccumsan tortor posuere ac ut consequat semper Hac habitasse platea dictumst quisque sagittis pur, Hac habitasse platea dictumst quisque sagittis pur Netus et malesuada fames ac turpisAccumsan tortor posuere ac ut consequat semperAccumsan tortor posuere ac ut consequat semperAccumsan tortor posuere ac ut consequat semper Hac habitasse platea dictumst quisque sagittis pur, Hac habitasse platea dictumst quisque sagittis pur Netus et malesuada fames ac turpis",
        url: "#",
        dateUpdated: "2018-09-04T16:56:37.423Z",
        location: [
            {
                name: "Really",
                knowledgeCategoryID: 1,
                parentID: 1,
                url: "#",
            },
            {
                name: "Cool",
                knowledgeCategoryID: 1,
                parentID: 1,
                url: "#",
            },
            {
                name: "Distant",
                knowledgeCategoryID: 1,
                parentID: 1,
                url: "#",
            },
            {
                name: "Location",
                knowledgeCategoryID: 1,
                parentID: 1,
                url: "#",
            },
        ],
    },
    {
        name: null,
        body: null,
        url: "#",
        dateUpdated: "2018-11-01T16:56:37.423Z",
    },
];
