/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import { actions } from "@knowledge/modules/editor/state";
import * as route from "@knowledge/modules/editor/route";
import { thunks as articleThunks } from "@knowledge/modules/article/state";
import { History } from "history";
import {
    IPostArticleRevisionRequestBody,
    IPostArticleRequestBody,
    IPostArticleResponseBody,
    IPostArticleRevisionResponseBody,
    IGetArticleResponseBody,
} from "@knowledge/@types/api";
import { apiThunk } from "@library/state/utility";
import pathToRegexp from "path-to-regexp";
import { AxiosResponse } from "axios";

// Usable action for getting an article
function postArticle(data: IPostArticleRequestBody) {
    return apiThunk("post", `/articles`, actions.postArticleActions, data);
}

// Usable action for getting an article
function getRevision(id: number | string) {
    return apiThunk("get", `/article-revisions/${id}`, actions.getRevisionActions, {});
}

// Usable action for getting an article
function postRevision(data: IPostArticleRevisionRequestBody) {
    return apiThunk("post", `/article-revisions`, actions.postRevisionActions, data);
}

function getEditArticle(id: string) {
    return apiThunk("get", `/articles/${id}`, actions.getArticleActions, {});
}

/**
 * Initialize the editor page data based on our path.
 *
 * We have to scenarios:
 *
 * - /articles/add - Initialize a new article
 * - /articles/:id/editor - We already have a new article. Go fetch it.
 *
 * @param history - The history object.
 */
export function initPageFromLocation(history: History) {
    return async dispatch => {
        const { location } = history;
        // Use the same path regex as our router.
        const addRegex = pathToRegexp(route.ADD_ROUTE);
        const editRegex = pathToRegexp(route.EDIT_ROUTE);

        // Check url
        if (addRegex.test(location.pathname)) {
            // We don't have an article so go create one.
            const article: AxiosResponse<IPostArticleResponseBody> = await dispatch(
                postArticle({ knowledgeCategoryID: 0 }),
            );
            const replacementUrl = route.makeEditUrl(article.data.articleID);
            const newLocation = {
                ...location,
                pathname: replacementUrl,
            };

            history.replace(newLocation);
        } else if (editRegex.test(location.pathname)) {
            // We don't have an article, but we have ID for one. Go get it.
            const articleID = editRegex.exec(location.pathname)![1];
            const article: AxiosResponse<IGetArticleResponseBody> = await dispatch(getEditArticle(articleID));
            dispatch(getRevision(article.data.articleRevisionID));
        }
    };
}

/**
 * Submit the editor's form data to the API.
 *
 * @param body - The body of the submit request.
 */
export function submitNewRevision(body: IPostArticleRevisionRequestBody, history: History) {
    return async dispatch => {
        const result: AxiosResponse<IPostArticleRevisionResponseBody> = await dispatch(postRevision(body));
        // Our API request has failed
        if (!result) {
            return;
        }

        const { articleID } = result.data;
        const newArticle: AxiosResponse<IGetArticleResponseBody> = await dispatch(articleThunks.getArticle(articleID));
        // Our API request failed.
        if (!newArticle) {
            return;
        }
        const { url } = newArticle.data;

        // Make the URL relative to the root of the site.
        const link = document.createElement("a");
        link.href = url;

        // Redirect to the new url.
        history.push(link.pathname);
    };
}
