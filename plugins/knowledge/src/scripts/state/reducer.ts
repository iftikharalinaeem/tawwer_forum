/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import { combineReducers } from "redux";
import CategoryModel from "@knowledge/modules/categories/CategoryModel";
import LocationPickerModel from "@knowledge/modules/locationPicker/LocationPickerModel";
import ArticlePageReducer from "@knowledge/modules/article/ArticlePageReducer";
import EditorPageModel from "@knowledge/modules/editor/EditorPageModel";
import CategoriesPageReducer from "@knowledge/modules/categories/CategoriesPageReducer";

const knowledgeReducer = combineReducers({
    articlePage: new ArticlePageReducer().reducer,
    editorPage: new EditorPageModel().reducer,
    categories: new CategoryModel().reducer,
    categoriesPage: new CategoriesPageReducer().reducer,
    locationPicker: new LocationPickerModel().reducer,
});

export default knowledgeReducer;
