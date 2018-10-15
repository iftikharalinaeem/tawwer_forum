/**
 * Contains the root AppState interface
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IEditorPageState } from "@knowledge/modules/editor/EditorPageReducer";
import { IKbCategoriesState } from "@knowledge/modules/categories/CategoryModel";
import { ILocationPickerState } from "@knowledge/modules/locationPicker/LocationPickerModel";
import { IArticlePageState } from "@knowledge/modules/article/ArticlePageReducer";

export interface IStoreState {
    knowledge: {
        articlePage: IArticlePageState;
        editorPage: IEditorPageState;
        categories: IKbCategoriesState;
        locationPicker: ILocationPickerState;
    };
}
