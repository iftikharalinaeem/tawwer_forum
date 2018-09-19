/**
 * @author Stéphane (slafleche) LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import { produce, Draft } from "immer";
import * as articleActions from "@knowledge/state/articleActions";
import * as revisionActions from "@knowledge/state/revisionActions";
import { LoadStatus } from "@library/@types/api";
import { IEditorPageState } from "@knowledge/@types/state";

export const initialState: IEditorPageState = {
    article: {
        status: LoadStatus.PENDING,
    },
    revision: {
        status: LoadStatus.PENDING,
    },
};

export default function editorPageReducer(
    state: IEditorPageState = initialState,
    action: articleActions.ActionTypes | revisionActions.ActionTypes,
): IEditorPageState {
    return produce(state, (draft: Draft<IEditorPageState>) => {
        switch (action.type) {
            case articleActions.POST_ARTICLE_REQUEST:
            case articleActions.GET_ARTICLE_REQUEST:
                draft.article.status = LoadStatus.LOADING;
                break;
            case articleActions.POST_ARTICLE_RESPONSE:
            case articleActions.GET_ARTICLE_SUCCESS:
                draft.article.status = LoadStatus.SUCCESS;
                draft.article.data = action.payload.data;
                break;
            case articleActions.GET_ARTICLE_ERROR:
            case articleActions.POST_ARTICLE_ERROR:
                draft.article.status = LoadStatus.ERROR;
                draft.article.error = action.payload;
                break;
            case revisionActions.GET_REVISION_REQUEST:
                draft.revision.status = LoadStatus.LOADING;
                break;
            case revisionActions.POST_REVISION_ERROR:
                draft.revision.status = LoadStatus.ERROR;
                draft.revision.error = action.payload;
                break;
            case revisionActions.POST_REVISION_RESPONSE:
                draft.revision.status = LoadStatus.SUCCESS;
                draft.revision.data = action.payload.data;
                break;
        }
    });
}
