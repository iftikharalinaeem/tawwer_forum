/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import { combineReducers } from "redux";
import articlePageReducer from "@knowledge/pages/article/articlePageReducer";
import editorPageReducer from "@knowledge/pages/editor/editorPageReducer";

const knowledgeReducer = combineReducers({
    articlePage: articlePageReducer,
    editorPage: editorPageReducer,
});

export default knowledgeReducer;
