/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IKbCategoriesState } from "./types";
import { dummyData } from "./dummyData";

/**
 * Dummy category reducer that currently only contains hard-coded data.
 */
export default function categoriesReducer(state: IKbCategoriesState = dummyData, action: any): IKbCategoriesState {
    return state;
}
