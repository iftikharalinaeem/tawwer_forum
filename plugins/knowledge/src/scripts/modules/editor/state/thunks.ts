/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import { actions, constants } from "@knowledge/modules/editor/state";
import { Location } from "history";
import {
    IPostArticleRevisionRequestBody,
    IPostArticleRequestBody,
    IPostArticleResponseBody,
} from "@knowledge/@types/api";
import { replace } from "connected-react-router";
import { apiThunk } from "@library/state/utility";
import pathToRegexp from "path-to-regexp";
import { AxiosResponse } from "axios";

// Usable action for getting an article
function postArticle(data: IPostArticleRequestBody) {
    return apiThunk("post", `/articles`, actions.postArticle, data);
}

function getArticle(id: string) {
    return apiThunk("get", `/articles/${id}`, actions.getArticle, {});
}

export function initPageFromLocation(location: Location) {
    return (dispatch: any) => {
        // Use the same path regex as our router.
        const addRegex = pathToRegexp(constants.ADD_ROUTE);
        const editRegex = pathToRegexp(constants.EDIT_ROUTE);

        // Check url
        if (addRegex.test(location.pathname)) {
            dispatch(postArticle({ knowledgeCategoryID: 0 })).then(
                (article: AxiosResponse<IPostArticleResponseBody>) => {
                    const replacementUrl = `/kb/articles/${article.data.articleID}/editor`;
                    const newLocation = {
                        ...location,
                        pathname: replacementUrl,
                    };

                    dispatch(replace(newLocation));
                },
            );
        } else if (editRegex.test(location.pathname)) {
            const articleID = editRegex.exec(location.pathname)![1];
            dispatch(getArticle(articleID));
        }
    };
}

export function postRevision(body: IPostArticleRevisionRequestBody) {
    return;
}
