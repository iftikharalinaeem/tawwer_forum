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

/*
 * Dummy draft data for developing front-end components
 */
export const dummyDraftListData: IDraftPreview[] = [
    {
        id: 1,
        name: null,
        body: "Netus et malesuada fames ac turpis.",
        url: "/kb/drafts/1",
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
        id: 2,
        name: "Google OAuth",
        body: null,
        url: "/kb/drafts/2",
        dateUpdated: "2018-04-30T16:56:37.423Z",
    },
    {
        id: 3,
        name: "Twitter SSO and Publishing",
        body:
            "Rig Veda shores of the cosmic ocean explorations of brilliant syntheses a still more glorious dawn awaits Vangelis. Are creatures of the cosmos gathered by gravity the only home we've ever known cosmic ocean Apollonius of Perga extraordinary claims require extraordinary evidence? Paroxysm of global death bits of moving fluff the carbon in our apple pies hundreds of thousands encyclopaedia galactica Sea of Tranquility. Courage of our questions two ghostly white figures in coveralls and helmets are soflty dancing Sea of Tranquility preserve and cherish that pale blue dot made in the interiors of collapsing stars two ghostly white figures in coveralls and helmets are soflty dancing and billions upon billions upon billions upon billions upon billions upon billions upon billions.",
        url: "/kb/drafts/3",
        dateUpdated: "2018-05-22T16:56:37.423Z",
    },
    {
        id: 4,
        name: "Simple Authentication Markup Language, Simple Authentication Markup Language",
        body:
            "Accumsan tortor posuere ac ut consequat semperAccumsan tortor posuere ac ut consequat semperAccumsan tortor posuere ac ut consequat semper Hac habitasse platea dictumst quisque sagittis pur, Hac habitasse platea dictumst quisque sagittis pur Netus et malesuada fames ac turpisAccumsan tortor posuere ac ut consequat semperAccumsan tortor posuere ac ut consequat semperAccumsan tortor posuere ac ut consequat semper Hac habitasse platea dictumst quisque sagittis pur, Hac habitasse platea dictumst quisque sagittis pur Netus et malesuada fames ac turpis",
        url: "/kb/drafts/4",
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
        id: 5,
        name: null,
        body: null,
        url: "/kb/drafts/5",
        dateUpdated: "2018-11-01T16:56:37.423Z",
    },
];
