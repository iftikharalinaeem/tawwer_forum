/**
 * @author Stéphane (slafleche) LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import * as actions from "@knowledge/pages/editor/editorPageActions";
import { LoadStatus } from "@library/@types/api";
import { IEditorPageState } from "@knowledge/@types/state";

export const initialState: IEditorPageState = {
    status: LoadStatus.PENDING,
};

export default function editorPageReducer(
    state: IEditorPageState = initialState,
    action: actions.ActionTypes,
): IEditorPageState {
    switch (action.type) {
        case actions.POST_ARTICLE_REQUEST:
            return {
                status: LoadStatus.LOADING,
            };
        case actions.POST_ARTICLE_RESPONSE:
            return {
                status: LoadStatus.SUCCESS,
                data: {
                    article: action.payload.data,
                },
            };
        case actions.POST_ARTICLE_ERROR:
            return {
                status: LoadStatus.ERROR,
                error: action.payload,
            };
        case actions.POST_REVISION_ERROR:
            return {
                status: LoadStatus.ERROR,
                error: action.payload,
            };
        case actions.POST_REVISION_RESPONSE:
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
