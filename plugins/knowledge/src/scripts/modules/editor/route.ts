/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import { formatUrl } from "@library/application";
import { IArticleFragment, IRevisionFragment, IArticle, IRevision } from "@knowledge/@types/api";
import { IResponseArticleDraft } from "@knowledge/@types/api/article";

// Route constants
export const EDIT_ROUTE = "/kb/articles/:id(\\d+)/editor";
export const REVISIONS_ROUTE = "/kb/articles/:id(\\d+)/revisions/:revisionID(\\d+)?";
export const ADD_ROUTE = "/kb/articles/add";

/**
 * Get the route for editing a particular article ID.
 *
 * @param articleID - The articleID.
 */
export function makeEditUrl(articleOrRevison: IArticleFragment | IArticle | IRevisionFragment | IRevision) {
    if ("articleRevisionID" in articleOrRevison) {
        return formatUrl(
            `/kb/articles/${articleOrRevison.articleID}/editor?revisionID=${articleOrRevison.articleRevisionID}`,
        );
    } else {
        return formatUrl(`/kb/articles/${articleOrRevison.articleID}/editor`);
    }
}

/**
 * Get the route for editing a particular article ID.
 *
 * @param articleID - The articleID.
 */
export function makeRevisionsUrl(articleOrRevison: IArticleFragment | IArticle | IRevisionFragment | IRevision) {
    if ("articleRevisionID" in articleOrRevison) {
        return formatUrl(`/kb/articles/${articleOrRevison.articleID}/revisions/${articleOrRevison.articleRevisionID}`);
    } else {
        return formatUrl(`/kb/articles/${articleOrRevison.articleID}/revisions`);
    }
}

/**
 * Get the route for a particular draft.
 */
export function makeDraftUrl(draft: IResponseArticleDraft) {
    if (draft.recordType === "article" && draft.recordID !== null) {
        return formatUrl(`/kb/articles/${draft.recordID}/editor?draftID=${draft.draftID}`);
    } else {
        return formatUrl(`/kb/articles/add?draftID=${draft.draftID}`);
    }
}
