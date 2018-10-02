/**
 * Contains the root AppState interface
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import { model as editorModel } from "@knowledge/modules/editor/state";
import { model as articleModel } from "@knowledge/modules/article/state";
import { IKbCategoriesState } from "@knowledge/modules/categories/state";
import { ILocationPickerState } from "@knowledge/modules/locationPicker/state";

export interface IStoreState {
    knowledge: {
        articlePage: articleModel.IState;
        editorPage: editorModel.IState;
        categories: IKbCategoriesState;
        locationPicker: ILocationPickerState;
    };
}
