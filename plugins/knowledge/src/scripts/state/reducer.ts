/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import ArticleMenuModel from "@knowledge/modules/article/ArticleMenuModel";
import ArticleModel from "@knowledge/modules/article/ArticleModel";
import ArticlePageModel from "@knowledge/modules/article/ArticlePageModel";
import CategoriesPageReducer from "@knowledge/modules/categories/CategoriesPageReducer";
import CategoryModel from "@knowledge/modules/categories/CategoryModel";
import DraftsPageModel from "@knowledge/modules/drafts/DraftsPageModel";
import EditorPageModel from "@knowledge/modules/editor/EditorPageModel";
import RevisionsPageModel from "@knowledge/modules/editor/RevisionsPageModel";
import LocationPickerModel from "@knowledge/modules/locationPicker/LocationPickerModel";
import NavigationModel from "@knowledge/modules/navigation/NavigationModel";
import SearchPageModel from "@knowledge/modules/search/SearchPageModel";
import { IKbState } from "@knowledge/state/model";
import { combineReducers } from "redux";
import KnowledgeBaseModel from "@knowledge/knowledge-bases/KnowledgeBaseModel";
import routeReducer from "@knowledge/routes/RouteReducer";

const knowledgeReducer = combineReducers<IKbState>({
    articles: new ArticleModel().reducer,
    articleMenu: new ArticleMenuModel().reducer,
    articlePage: new ArticlePageModel().reducer,
    draftsPage: new DraftsPageModel().reducer,
    editorPage: new EditorPageModel().reducer,
    revisionsPage: new RevisionsPageModel().reducer,
    categories: new CategoryModel().reducer,
    categoriesPage: new CategoriesPageReducer().reducer,
    locationPicker: new LocationPickerModel().reducer,
    searchPage: new SearchPageModel().reducer,
    navigation: new NavigationModel().reducer,
    route: routeReducer,
    knowledgeBases: new KnowledgeBaseModel().reducer,
});

export default knowledgeReducer;
