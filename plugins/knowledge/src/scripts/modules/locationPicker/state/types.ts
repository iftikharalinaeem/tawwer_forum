/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import { LoadStatus } from "@library/@types/api";
import { IKbNavigationItem } from "@knowledge/@types/api";

export interface ILocationPickerState {
    status: LoadStatus;
    currentCategoryID: number; // A list of category IDs;
    currentFolderItems: IKbNavigationItem[];
}
