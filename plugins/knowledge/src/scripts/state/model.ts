/**
 * Contains the root AppState interface
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IKnowledgeBasesState } from "@knowledge/knowledge-bases/KnowledgeBaseModel";
import ArticleActions from "@knowledge/modules/article/ArticleActions";
import { IArticleMenuState } from "@knowledge/modules/article/ArticleMenuModel";
import { IArticleState } from "@knowledge/modules/article/ArticleModel";
import ArticlePageActions from "@knowledge/modules/article/ArticlePageActions";
import { IArticlePageState } from "@knowledge/modules/article/ArticlePageModel";
import CategoryActions from "@knowledge/modules/categories/CategoryActions";
import { IKbCategoriesState } from "@knowledge/modules/categories/CategoryModel";
import { ICategoriesPageState } from "@knowledge/modules/categories/categoryPageReducer";
import DraftsPageActions from "@knowledge/modules/drafts/DraftsPageActions";
import { IDraftsPageState } from "@knowledge/modules/drafts/DraftsPageModel";
import EditorPageActions from "@knowledge/modules/editor/EditorPageActions";
import { IEditorPageState } from "@knowledge/modules/editor/EditorPageModel";
import RevisionsPageActions from "@knowledge/modules/editor/RevisionsPageActions";
import { IRevisionsPageState } from "@knowledge/modules/editor/RevisionsPageModel";
import { ILocationPickerState } from "@knowledge/modules/locationPicker/LocationPickerModel";
import { ISearchPageState } from "@knowledge/modules/search/searchPageReducer";
import { INavigationStoreState } from "@knowledge/navigation/state/NavigationModel";
import { IRouteState } from "@knowledge/routes/RouteReducer";
import { ICoreStoreState } from "@library/redux/reducerRegistry";
import { DeepPartial, Reducer } from "redux";
import { IServerState } from "@knowledge/server/serverReducer";
import { IUnifySearchPageState } from "@knowledge/modules/search/unifySearchPageReducer";

export type KNOWLEDGE_ACTION =
    | typeof EditorPageActions.ACTION_TYPES
    | typeof CategoryActions.ACTION_TYPES
    | typeof ArticlePageActions.ACTION_TYPES
    | typeof ArticleActions.ACTION_TYPES
    | typeof RevisionsPageActions.ACTION_TYPES
    | typeof DraftsPageActions.ACTION_TYPES;

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
    unifySearchPage: IUnifySearchPageState;
    navigation: INavigationStoreState;
    route: IRouteState;
    knowledgeBases: IKnowledgeBasesState;
}

export interface IKnowledgeAppStoreState extends ICoreStoreState {
    knowledge: IKbState;
    server: IServerState;
}

export type IPartialStoreState = DeepPartial<IKnowledgeAppStoreState>;
