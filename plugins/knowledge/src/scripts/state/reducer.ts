/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import { combineReducers } from "redux";
import { reducer as categoriesReducer } from "@knowledge/modules/categories/state";
import LocationPickerReducer from "@knowledge/modules/locationPicker/LocationPickerReducer";
import ArticlePageReducer from "@knowledge/modules/article/ArticlePageReducer";
import EditorPageReducer from "@knowledge/modules/editor/EditorPageReducer";

const knowledgeReducer = combineReducers({
    articlePage: new ArticlePageReducer().reducer,
    editorPage: new EditorPageReducer().reducer,
    categories: categoriesReducer,
    locationPicker: new LocationPickerReducer().reducer,
});

export default knowledgeReducer;
