/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import ArticleMenuModel from "@knowledge/modules/article/ArticleMenuModel";
import ArticleModel from "@knowledge/modules/article/ArticleModel";
import { articlePageReducer } from "@knowledge/modules/article/ArticlePageModel";
import CategoryModel from "@knowledge/modules/categories/CategoryModel";
import DraftsPageModel from "@knowledge/modules/drafts/DraftsPageModel";
import EditorPageModel from "@knowledge/modules/editor/EditorPageModel";
import RevisionsPageModel from "@knowledge/modules/editor/RevisionsPageModel";
import LocationPickerModel from "@knowledge/modules/locationPicker/LocationPickerModel";
import NavigationModel from "@knowledge/navigation/state/NavigationModel";
import { searchPageReducer } from "@knowledge/modules/search/searchPageReducer";
import { unifySearchPageReducer } from "@knowledge/modules/search/unifySearchPageReducer";
import { IKbState } from "@knowledge/state/model";
import { combineReducers } from "redux";
import KnowledgeBaseModel from "@knowledge/knowledge-bases/KnowledgeBaseModel";
import routeReducer from "@knowledge/routes/RouteReducer";
import { categoryPageReducer } from "@knowledge/modules/categories/categoryPageReducer";

const knowledgeReducer = combineReducers<IKbState>({
    articles: new ArticleModel().reducer,
    articleMenu: new ArticleMenuModel().reducer,
    articlePage: articlePageReducer,
    draftsPage: new DraftsPageModel().reducer,
    editorPage: new EditorPageModel().reducer,
    revisionsPage: new RevisionsPageModel().reducer,
    categories: new CategoryModel().reducer,
    categoriesPage: categoryPageReducer,
    locationPicker: new LocationPickerModel().reducer,
    searchPage: searchPageReducer,
    unifySearchPage: unifySearchPageReducer,
    navigation: new NavigationModel().reducer,
    route: routeReducer,
    knowledgeBases: new KnowledgeBaseModel().reducer,
});

export default knowledgeReducer;
