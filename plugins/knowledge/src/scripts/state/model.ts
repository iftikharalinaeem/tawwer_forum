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
import { DeepPartial, Reducer } from "redux";
import { INavigationStoreState } from "@knowledge/modules/navigation/NavigationModel";
import { IRouteState } from "@knowledge/routes/RouteReducer";
import EditorPageActions from "@knowledge/modules/editor/EditorPageActions";
import CategoryActions from "@knowledge/modules/categories/CategoryActions";
import LocationPickerActions from "@knowledge/modules/locationPicker/LocationPickerActions";
import ArticlePageActions from "@knowledge/modules/article/ArticlePageActions";
import CategoriesPageActions from "@knowledge/modules/categories/CategoriesPageActions";
import ArticleActions from "@knowledge/modules/article/ArticleActions";
import RevisionsPageActions from "@knowledge/modules/editor/RevisionsPageActions";
import UsersActions from "@library/users/UsersActions";
import SearchPageActions from "@knowledge/modules/search/SearchPageActions";
import DraftsPageActions from "@knowledge/modules/drafts/DraftsPageActions";
import NavigationActions from "@knowledge/modules/navigation/NavigationActions";
import RouteActions from "@knowledge/routes/RouteActions";
import KnowledgeBaseActions from "@knowledge/knowledge-bases/KnowledgeBaseActions";
import { IKnowledgeBasesState } from "@knowledge/knowledge-bases/KnowledgeBaseModel";

export type KNOWLEDGE_ACTION =
    | typeof EditorPageActions.ACTION_TYPES
    | typeof CategoryActions.ACTION_TYPES
    | typeof LocationPickerActions.ACTION_TYPES
    | typeof ArticlePageActions.ACTION_TYPES
    | typeof CategoriesPageActions.ACTION_TYPES
    | typeof ArticleActions.ACTION_TYPES
    | typeof RevisionsPageActions.ACTION_TYPES
    | typeof UsersActions.ACTION_TYPES
    | typeof SearchPageActions.ACTION_TYPES
    | typeof DraftsPageActions.ACTION_TYPES
    | typeof NavigationActions.ACTION_TYPES
    | typeof RouteActions.ACTION_TYPES
    | typeof KnowledgeBaseActions.ACTION_TYPES;

export type KnowledgeReducer<S> = Reducer<S, KNOWLEDGE_ACTION>;

export interface IKbState {
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
    knowledgeBases: IKnowledgeBasesState;
}

export interface IStoreState extends IUsersStoreState {
    knowledge: IKbState;
}

export type IPartialStoreState = DeepPartial<IStoreState>;
