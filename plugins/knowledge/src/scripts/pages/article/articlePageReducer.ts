/**
 * @author Stéphane (slafleche) LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import * as actions from "@knowledge/pages/article/articlePageActions";
import { LoadStatus } from "@dashboard/@types/api";
import { IArticlePageState } from "@knowledge/@types/state";

const initialState: IArticlePageState = {
    status: LoadStatus.PENDING,
};

export default function articlePageReducer(
    state: IArticlePageState = initialState,
    action: actions.ActionTypes,
): IArticlePageState {
    switch (action.type) {
        case actions.GET_ARTICLE_REQUEST:
            return {
                ...state,
                status: LoadStatus.LOADING,
            };
        case actions.GET_ARTICLE_SUCCESS:
            return {
                status: LoadStatus.SUCCESS,
                data: {
                    article: action.payload.data,
                },
                error: undefined,
            };
        case actions.GET_ARTICLE_ERROR:
            return {
                ...state,
                status: LoadStatus.ERROR,
                error: action.payload,
            };
        case actions.CLEAR_ARTICLE_PAGE_STATE:
            return initialState;
        default:
            return state;
    }
}
