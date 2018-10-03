/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import { ILoadable } from "@library/@types/api";
import { IKbNavigationItem } from "@knowledge/@types/api";

export interface ILocationPickerState {
    selectedCategoryID: number;
    navigatedCategoryID: number;
    chosenCategoryID: number;
    items: ILoadable<IKbNavigationItem[]>;
}
