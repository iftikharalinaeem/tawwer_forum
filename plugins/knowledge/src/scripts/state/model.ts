/**
 * Contains the root AppState interface
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IEditorPageState } from "@knowledge/modules/editor/EditorPageModel";
import { IKbCategoriesState } from "@knowledge/modules/categories/CategoryModel";
import { ILocationPickerState } from "@knowledge/modules/locationPicker/LocationPickerModel";
import { IArticlePageState } from "@knowledge/modules/article/ArticlePageReducer";
import { ICategoriesPageState } from "@knowledge/modules/categories/CategoriesPageReducer";
import { IArticleMenuState } from "@knowledge/modules/article/ArticleMenuModel";
import { IUsersStoreState } from "@library/users/UsersModel";

type RecursivePartial<T> = {
    [P in keyof T]?: T[P] extends Array<infer U>
        ? Array<RecursivePartial<U>>
        : T[P] extends object ? RecursivePartial<T[P]> : T[P]
};

export interface IStoreState extends IUsersStoreState {
    knowledge: {
        articleMenu: IArticleMenuState;
        articlePage: IArticlePageState;
        editorPage: IEditorPageState;
        categories: IKbCategoriesState;
        categoriesPage: ICategoriesPageState;
        locationPicker: ILocationPickerState;
    };
}

export type IPartialStoreState = RecursivePartial<IStoreState>;
