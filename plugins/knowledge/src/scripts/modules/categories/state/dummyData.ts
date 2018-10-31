/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IKbCategoriesState } from "@knowledge/modules/categories/CategoryModel";
import { KbCategoryDisplayType } from "@knowledge/@types/api";
import { LoadStatus } from "@library/@types/api";

export const dummyData: IKbCategoriesState = {
    status: LoadStatus.SUCCESS,
    data: {
        categoriesByID: {
            1: {
                knowledgeCategoryID: 1,
                name: "Example Root",
                parentID: -1,
                url: "https://dev.vanilla.localhost/kb/categories/1-example-root",
            },
            2: {
                knowledgeCategoryID: 2,
                name: "Pee Mart",
                parentID: 1,
                url: "https://dev.vanilla.localhost/kb/categories/2-pee-mart",
            },
            3: {
                knowledgeCategoryID: 3,
                name: "Predator Urine",
                parentID: 2,
                url: "https://dev.vanilla.localhost/kb/categories/3-predator-urine",
            },
            4: {
                knowledgeCategoryID: 4,
                name: "Coyote Urine",
                parentID: 3,
                url: "https://dev.vanilla.localhost/kb/categories/4-coyote-urine",
            },
            5: {
                knowledgeCategoryID: 5,
                name: "Fox Urine",
                parentID: 3,
                url: "https://dev.vanilla.localhost/kb/categories/5-fox-urine",
            },
            6: {
                knowledgeCategoryID: 6,
                name: "Bobcat Urine",
                parentID: 3,
                url: "https://dev.vanilla.localhost/kb/categories/6-bobcat-urine",
            },
            7: {
                knowledgeCategoryID: 7,
                name: "P-Gel",
                parentID: 2,
                url: "https://dev.vanilla.localhost/kb/categories/7-p-gel",
            },
            8: {
                knowledgeCategoryID: 8,
                name: "P-Cover Granules",
                parentID: 2,
                url: "https://dev.vanilla.localhost/kb/categories/8-p-cover-granules",
            },
            9: {
                knowledgeCategoryID: 9,
                name: "Prey Animals",
                parentID: 2,
                url: "https://dev.vanilla.localhost/kb/categories/9-prey-animals",
            },
            10: {
                knowledgeCategoryID: 10,
                name: "Armadillos",
                parentID: 9,
                url: "https://dev.vanilla.localhost/kb/categories/10-armadillos",
            },
            11: {
                knowledgeCategoryID: 11,
                name: "Chipmunks",
                parentID: 9,
                url: "https://dev.vanilla.localhost/kb/categories/11-chipmunks",
            },
            12: {
                knowledgeCategoryID: 12,
                name: "Dispensers",
                parentID: 2,
                url: "https://dev.vanilla.localhost/kb/categories/12-dispensers",
            },
            13: {
                knowledgeCategoryID: 13,
                name: "Mountain Lion",
                parentID: 8,
                url: "https://dev.vanilla.localhost/kb/categories/13-mountain-lion",
            },
            14: {
                knowledgeCategoryID: 14,
                name: "Bear",
                parentID: 8,
                url: "https://dev.vanilla.localhost/kb/categories/14-bear",
            },
            15: {
                knowledgeCategoryID: 15,
                name: "Wolf",
                parentID: 8,
                url: "https://dev.vanilla.localhost/kb/categories/15-wolf",
            },
            16: {
                knowledgeCategoryID: 16,
                name: "P-Wicks",
                parentID: 12,
                url: "https://dev.vanilla.localhost/kb/categories/16-p-wicks",
            },
            17: {
                knowledgeCategoryID: 17,
                name: "P-Dispensers",
                parentID: 12,
                url: "https://dev.vanilla.localhost/kb/categories/17-p-dispensers",
            },
        },
    },
};
