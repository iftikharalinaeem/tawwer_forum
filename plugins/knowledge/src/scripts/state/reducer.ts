/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import { combineReducers } from "redux";
import CategoryModel from "@knowledge/modules/categories/CategoryModel";
import LocationPickerModel from "@knowledge/modules/locationPicker/LocationPickerModel";
import ArticlePageModel from "@knowledge/modules/article/ArticlePageModel";
import EditorPageModel from "@knowledge/modules/editor/EditorPageModel";
import CategoriesPageReducer from "@knowledge/modules/categories/CategoriesPageReducer";
import ArticleMenuModel from "@knowledge/modules/article/ArticleMenuModel";
import ArticleModel from "@knowledge/modules/article/ArticleModel";
import RevisionsPageModel from "@knowledge/modules/editor/RevisionsPageModel";
import SearchPageModel from "@knowledge/modules/search/SearchPageModel";
import DraftsPageModel from "@knowledge/modules/drafts/DraftsPageModel";
import NavigationModel from "@knowledge/modules/navigation/NavigationModel";

const knowledgeReducer = combineReducers({
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
});

export default knowledgeReducer;
