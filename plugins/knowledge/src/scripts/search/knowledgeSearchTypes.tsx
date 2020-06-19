/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IComboBoxOption } from "@vanilla/library/src/scripts/features/search/SearchBar";

export interface IKnowledgeSearchTypes {
    knowledgeBaseID?: number;
    knowledgeBaseOption: IComboBoxOption;
    includeDeleted?: boolean;
}
