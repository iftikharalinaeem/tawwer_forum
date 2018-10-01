/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import { combineReducers } from "redux";
import { reducer as articleReducer } from "@knowledge/modules/article/state";
import { reducer as editorReducer } from "@knowledge/modules/editor/state";

const knowledgeReducer = combineReducers({
    articlePage: articleReducer,
    editorPage: editorReducer,
});

export default knowledgeReducer;
