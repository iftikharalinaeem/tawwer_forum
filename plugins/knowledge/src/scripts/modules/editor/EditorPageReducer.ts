/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import { LoadStatus, ILoadable } from "@library/@types/api";
import ReduxReducer from "@library/state/ReduxReducer";
import EditorPageActions from "@knowledge/modules/editor/EditorPageActions";
import produce from "immer";
import { IArticle, IArticleRevision } from "@knowledge/@types/api";

export interface IEditorPageState {
    article: ILoadable<IArticle>;
    revision: ILoadable<IArticleRevision | undefined>;
    submit: ILoadable<{}>;
}

/**
 * Reducer for the article page.
 */
export default class EditorPageReducer extends ReduxReducer<IEditorPageState> {
    public initialState: IEditorPageState = {
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

    public reducer = (state = this.initialState, action: typeof EditorPageActions.ACTION_TYPES): IEditorPageState => {
        return produce(state, draft => {
            switch (action.type) {
                case EditorPageActions.POST_ARTICLE_REQUEST:
                    draft.article.status = LoadStatus.LOADING;
                    break;
                case EditorPageActions.GET_ARTICLE_REQUEST:
                    draft.article.status = LoadStatus.LOADING;
                    // When fetching an existing article, we will also need to look for
                    // An existing revision.
                    draft.revision.status = LoadStatus.LOADING;
                    break;
                case EditorPageActions.GET_ARTICLE_RESPONSE:
                case EditorPageActions.POST_ARTICLE_RESPONSE:
                    draft.article.status = LoadStatus.SUCCESS;
                    draft.article.data = action.payload.data;
                    // When receving an article, not having a revision ID means there is nothing new to load.
                    // As a result we need to clear the optomistic loading indicator we put up earlier.
                    // as the user will be making a totally new revision.
                    if (action.payload.data.articleRevisionID === null) {
                        draft.revision.status = LoadStatus.PENDING;
                    }
                    break;
                case EditorPageActions.GET_ARTICLE_ERROR:
                case EditorPageActions.POST_ARTICLE_ERROR:
                    draft.article.status = LoadStatus.ERROR;
                    draft.article.error = action.payload;
                    break;
                // Getting an existing revision.
                case EditorPageActions.GET_REVISION_REQUEST:
                    draft.revision.status = LoadStatus.LOADING;
                    break;
                case EditorPageActions.GET_REVISION_ERROR:
                    draft.revision.status = LoadStatus.ERROR;
                    draft.revision.error = action.payload;
                    break;
                case EditorPageActions.GET_REVISION_RESPONSE:
                    draft.revision.status = LoadStatus.SUCCESS;
                    draft.revision.data = action.payload.data;
                    break;
                // Submitting a new revision.
                case EditorPageActions.POST_REVISION_REQUEST:
                    draft.submit.status = LoadStatus.LOADING;
                    break;
                case EditorPageActions.POST_REVISION_ERROR:
                    draft.submit.status = LoadStatus.ERROR;
                    draft.submit.error = action.payload;
                    break;
                case EditorPageActions.POST_REVISION_RESPONSE:
                    draft.submit.status = LoadStatus.SUCCESS;
                    break;
                case EditorPageActions.RESET:
                    return this.initialState;
            }
        });
    };
}
