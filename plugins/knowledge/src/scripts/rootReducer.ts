/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import { combineReducers } from "redux";
import articlePageReducer from "@knowledge/pages/article/articlePageReducer";

const knowledgeReducer = combineReducers({
    articlePage: articlePageReducer,
});

export default knowledgeReducer;
