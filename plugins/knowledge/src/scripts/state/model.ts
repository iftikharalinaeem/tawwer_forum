/**
 * Contains the root AppState interface
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IEditorPageState } from "@knowledge/modules/editor/EditorPageModel";
import { IKbCategoriesState } from "@knowledge/modules/categories/CategoryModel";
import { ILocationPickerState } from "@knowledge/modules/locationPicker/LocationPickerModel";
import { IArticlePageState } from "@knowledge/modules/article/ArticlePageModel";
import { ICategoriesPageState } from "@knowledge/modules/categories/CategoriesPageReducer";
import { IArticleMenuState } from "@knowledge/modules/article/ArticleMenuModel";
import { IArticleState } from "@knowledge/modules/article/ArticleModel";
import { IRevisionsPageState } from "@knowledge/modules/editor/RevisionsPageModel";
import { IUsersStoreState } from "@library/users/UsersModel";
import { ISearchPageState } from "@knowledge/modules/search/SearchPageModel";
import { IDraftsPageState } from "@knowledge/modules/drafts/DraftsPageModel";
import { DeepPartial } from "redux";
import { INavigationStoreState } from "@knowledge/modules/navigation/NavigationModel";
import { IRouteState } from "@knowledge/routes/RouteReducer";

export interface IStoreState extends IUsersStoreState {
    knowledge: {
        articles: IArticleState;
        articleMenu: IArticleMenuState;
        articlePage: IArticlePageState;
        draftsPage: IDraftsPageState;
        revisionsPage: IRevisionsPageState;
        editorPage: IEditorPageState;
        categories: IKbCategoriesState;
        categoriesPage: ICategoriesPageState;
        locationPicker: ILocationPickerState;
        searchPage: ISearchPageState;
        navigation: INavigationStoreState;
        route: IRouteState;
    };
}

export type IPartialStoreState = DeepPartial<IStoreState>;
