/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import * as actions from "./actions";
import { apiThunk } from "@library/state/utility";

// Usable action for getting an article
export function getArticle(id: number) {
    return apiThunk("get", `/articles/${id}?expand=all`, actions.getArticleActions, {});
}
