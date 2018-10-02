/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import { combineReducers } from "redux";
import { reducer as articleReducer } from "@knowledge/modules/article/state";
import { reducer as editorReducer } from "@knowledge/modules/editor/state";
import { reducer as categoriesReducer } from "@knowledge/modules/categories/state";
import { reducer as locationPickerReducer } from "@knowledge/modules/locationPicker/state";

const knowledgeReducer = combineReducers({
    articlePage: articleReducer,
    editorPage: editorReducer,
    categories: categoriesReducer,
    locationPicker: locationPickerReducer,
});

export default knowledgeReducer;
