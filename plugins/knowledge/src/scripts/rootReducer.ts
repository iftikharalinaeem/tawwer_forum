/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import { combineReducers } from "redux";
import articlePageReducer from "@knowledge/pages/article/articlePageReducer";

const knowledgeReducer = combineReducers({
    articlePage: articlePageReducer,
});

export default knowledgeReducer;
