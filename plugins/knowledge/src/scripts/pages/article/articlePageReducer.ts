/**
 * @author Stéphane (slafleche) LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import * as pageActions from "@knowledge/pages/article/articlePageActions";
import * as articleActions from "@knowledge/state/articleActions";
import { LoadStatus } from "@library/@types/api";
import { IArticlePageState } from "@knowledge/@types/state";

export const initialState: IArticlePageState = {
    status: LoadStatus.PENDING,
};

export default function articlePageReducer(
    state: IArticlePageState = initialState,
    action: pageActions.ActionTypes | articleActions.ActionTypes,
): IArticlePageState {
    switch (action.type) {
        case articleActions.GET_ARTICLE_REQUEST:
            return {
                status: LoadStatus.LOADING,
            };
        case articleActions.GET_ARTICLE_SUCCESS:
            return {
                status: LoadStatus.SUCCESS,
                data: {
                    article: action.payload.data,
                },
            };
        case articleActions.GET_ARTICLE_ERROR:
            return {
                status: LoadStatus.ERROR,
                error: action.payload,
            };
        case pageActions.RESET_PAGE_STATE:
            return initialState;
        default:
            return state;
    }
}
