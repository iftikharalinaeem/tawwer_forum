/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import { produce, Draft } from "immer";
import { LoadStatus } from "@library/@types/api";
import { model, actions, constants } from "@knowledge/modules/editor/state";

export const initialState: model.IState = {
    article: {
        status: LoadStatus.PENDING,
    },
    revision: {
        status: LoadStatus.PENDING,
    },
    submit: {
        status: LoadStatus.PENDING,
    },
};

/**
 * Reducer for the editor page.
 *
 * @param state - The currrent state.
 * @param action - The action being taken.
 */
export default function editorPageReducer(
    state: model.IState = initialState,
    action: actions.ActionTypes,
): model.IState {
    return produce(state, (draft: Draft<model.IState>) => {
        switch (action.type) {
            // POST_ARTICLE & GET_ARTICLE are handled the same way right now.
            case constants.POST_ARTICLE_REQUEST:
            case constants.GET_ARTICLE_REQUEST:
                draft.article.status = LoadStatus.LOADING;
                break;
            case constants.POST_ARTICLE_RESPONSE:
            case constants.GET_ARTICLE_RESPONSE:
                draft.article.status = LoadStatus.SUCCESS;
                draft.article.data = action.payload.data;
                break;
            case constants.GET_ARTICLE_ERROR:
            case constants.POST_ARTICLE_ERROR:
                draft.article.status = LoadStatus.ERROR;
                draft.article.error = action.payload;
                break;
            // Getting an existing revision.
            case constants.GET_REVISION_REQUEST:
                draft.revision.status = LoadStatus.LOADING;
                break;
            case constants.GET_REVISION_ERROR:
                draft.revision.status = LoadStatus.ERROR;
                draft.revision.error = action.payload;
                break;
            case constants.GET_REVISION_RESPONSE:
                draft.revision.status = LoadStatus.SUCCESS;
                draft.revision.data = action.payload.data;
                break;
            // Submitting a new revision.
            case constants.POST_REVISION_REQUEST:
                draft.submit.status = LoadStatus.LOADING;
                break;
            case constants.POST_REVISION_ERROR:
                draft.submit.status = LoadStatus.ERROR;
                draft.submit.error = action.payload;
                break;
            case constants.POST_REVISION_RESPONSE:
                draft.submit.status = LoadStatus.SUCCESS;
                break;
        }
    });
}
