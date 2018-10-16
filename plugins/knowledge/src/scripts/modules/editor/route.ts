/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import { formatUrl } from "@library/application";
import { match } from "react-router-dom";

// Route constants
export const EDIT_ROUTE = "/kb/articles/:id/editor";
export const ADD_ROUTE = "/kb/articles/add";
export const ADD_EDIT_ROUTE = "/kb/articles/(\\d+/editor|add)";

/**
 * Get the route for editing a particular article ID.
 *
 * @param articleID - The articleID.
 */
export function makeEditUrl(articleID: string | number) {
    return formatUrl(`/kb/articles/${articleID}/editor`);
}
