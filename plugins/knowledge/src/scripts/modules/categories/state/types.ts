/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IKbCategoryFragment } from "@knowledge/@types/api";

export interface IKbCategoriesState {
    categoriesByID: {
        [id: number]: IKbCategoryFragment;
    };
}
