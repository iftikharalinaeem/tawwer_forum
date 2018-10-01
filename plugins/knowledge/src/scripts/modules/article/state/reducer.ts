/**
 * @author Stéphane (slafleche) LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import { model, actions, constants } from "@knowledge/modules/article/state";
import { LoadStatus } from "@library/@types/api";

export const initialState: model.IState = {
    status: LoadStatus.PENDING,
};

export default function articlePageReducer(
    state: model.IState = initialState,
    action: actions.ActionTypes,
): model.IState {
    switch (action.type) {
        case constants.GET_ARTICLE_REQUEST:
            return {
                status: LoadStatus.LOADING,
            };
        case constants.GET_ARTICLE_RESPONSE:
            return {
                status: LoadStatus.SUCCESS,
                data: {
                    article: action.payload.data,
                },
            };
        case constants.GET_ARTICLE_ERROR:
            return {
                status: LoadStatus.ERROR,
                error: action.payload,
            };
        case constants.RESET_PAGE_STATE:
            return initialState;
        default:
            return state;
    }
}
