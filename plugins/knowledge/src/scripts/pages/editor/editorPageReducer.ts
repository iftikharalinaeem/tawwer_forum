/**
 * @author Stéphane (slafleche) LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import * as articleActions from "@knowledge/state/articleActions";
import { LoadStatus } from "@library/@types/api";
import { IEditorPageState } from "@knowledge/@types/state";

export const initialState: IEditorPageState = {
    status: LoadStatus.PENDING,
};

export default function editorPageReducer(
    state: IEditorPageState = initialState,
    action: articleActions.ActionTypes,
): IEditorPageState {
    switch (action.type) {
        case articleActions.POST_ARTICLE_REQUEST:
            return {
                status: LoadStatus.LOADING,
            };
        case articleActions.POST_ARTICLE_RESPONSE: {
            const newArticleId = action.payload.data.articleID;

            return {
                status: LoadStatus.SUCCESS,
                data: {
                    article: action.payload.data,
                },
            };
        }
        case articleActions.POST_ARTICLE_ERROR:
            return {
                status: LoadStatus.ERROR,
                error: action.payload,
            };
        case articleActions.POST_REVISION_ERROR:
            return {
                status: LoadStatus.ERROR,
                error: action.payload,
            };
        case articleActions.POST_REVISION_RESPONSE:
            return {
                status: LoadStatus.SUCCESS,
                data: {
                    ...state!.data,
                    revision: action.payload.data,
                },
            };
        default:
            return state;
    }
}
