/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IKbCategoriesState } from "./types";
import { KbCategoryDisplayType } from "@knowledge/@types/api";

export const dummyData: IKbCategoriesState = {
    categoriesByID: {
        1: {
            name: "Top level category",
            displayType: KbCategoryDisplayType.GUIDE,
            isSection: false,
            url: "https://dev.vanilla.localhost/knowledge/category/top-level-cateogry-1",
            parentID: -1,
            knowledgeCategoryID: 1,
        },
        2: {
            name: "Predator Urine",
            displayType: KbCategoryDisplayType.GUIDE,
            isSection: false,
            url: "https://dev.vanilla.localhost/knowledge/category/predator-urine-2",
            parentID: 1,
            knowledgeCategoryID: 2,
        },
        3: {
            name: "Coyote Urine",
            displayType: KbCategoryDisplayType.GUIDE,
            isSection: false,
            url: "https://dev.vanilla.localhost/knowledge/category/coyote-urine-3",
            parentID: 2,
            knowledgeCategoryID: 3,
        },
        4: {
            name: "Fox Urine",
            displayType: KbCategoryDisplayType.GUIDE,
            isSection: false,
            url: "https://dev.vanilla.localhost/knowledge/category/fox-urine-4",
            parentID: 2,
            knowledgeCategoryID: 4,
        },
        5: {
            name: "Bobcat Urine",
            displayType: KbCategoryDisplayType.GUIDE,
            isSection: false,
            url: "https://dev.vanilla.localhost/knowledge/category/bobcat-urine-5",
            parentID: 2,
            knowledgeCategoryID: 5,
        },
        6: {
            name: "P-Gel",
            displayType: KbCategoryDisplayType.GUIDE,
            isSection: false,
            url: "https://dev.vanilla.localhost/knowledge/category/p-gel-6",
            parentID: 1,
            knowledgeCategoryID: 6,
        },
        7: {
            name: "P-Cover Granules",
            displayType: KbCategoryDisplayType.GUIDE,
            isSection: true,
            url: "https://dev.vanilla.localhost/knowledge/category/p-cover-granules-7",
            parentID: 1,
            knowledgeCategoryID: 7,
        },
        8: {
            name: "Prey Animals",
            displayType: KbCategoryDisplayType.GUIDE,
            isSection: false,
            url: "https://dev.vanilla.localhost/knowledge/category/prey-animals-8",
            parentID: 1,
            knowledgeCategoryID: 8,
        },
        9: {
            name: "Armadillos ",
            displayType: KbCategoryDisplayType.GUIDE,
            isSection: false,
            url: "https://dev.vanilla.localhost/knowledge/category/armadillos-9",
            parentID: 8,
            knowledgeCategoryID: 9,
        },
        10: {
            name: "Chipmunks",
            displayType: KbCategoryDisplayType.GUIDE,
            isSection: false,
            url: "https://dev.vanilla.localhost/knowledge/category/chipmunks-10",
            parentID: 8,
            knowledgeCategoryID: 10,
        },
        11: {
            name: "Dispensers",
            displayType: KbCategoryDisplayType.SEARCH,
            isSection: true,
            url: "https://dev.vanilla.localhost/knowledge/category/dispensers-11",
            parentID: 1,
            knowledgeCategoryID: 11,
        },
    },
};
