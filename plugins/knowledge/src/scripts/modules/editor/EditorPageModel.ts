/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import { LoadStatus, ILoadable } from "@library/@types/api";
import ReduxReducer from "@library/state/ReduxReducer";
import EditorPageActions from "@knowledge/modules/editor/EditorPageActions";
import produce from "immer";
import { IArticle, Format } from "@knowledge/@types/api";
import ArticlePageActions from "@knowledge/modules/article/ArticlePageActions";

export interface IEditorPageState {
    article: ILoadable<IArticle>;
    submit: ILoadable<{}>;
}

/**
 * Reducer for the article page.
 */
export default class EditorPageModel extends ReduxReducer<IEditorPageState> {
    public initialState: IEditorPageState = {
        article: {
            status: LoadStatus.PENDING,
        },

        submit: {
            status: LoadStatus.PENDING,
        },
    };

    public reducer = (
        state = this.initialState,
        action: typeof EditorPageActions.ACTION_TYPES | typeof ArticlePageActions.ACTION_TYPES,
    ): IEditorPageState => {
        return produce(state, draft => {
            switch (action.type) {
                case EditorPageActions.POST_ARTICLE_REQUEST:
                    draft.article.status = LoadStatus.LOADING;
                    break;
                case EditorPageActions.GET_ARTICLE_REQUEST:
                    draft.article.status = LoadStatus.LOADING;
                    break;
                case EditorPageActions.GET_ARTICLE_RESPONSE:
                case EditorPageActions.POST_ARTICLE_RESPONSE:
                    draft.article.status = LoadStatus.SUCCESS;
                    draft.article.data = action.payload.data;
                    break;
                case EditorPageActions.GET_ARTICLE_ERROR:
                case EditorPageActions.POST_ARTICLE_ERROR:
                    draft.article.status = LoadStatus.ERROR;
                    draft.article.error = action.payload;
                    break;
                // Patching the article
                case EditorPageActions.PATCH_ARTICLE_REQUEST:
                    draft.submit.status = LoadStatus.LOADING;
                    break;
                case EditorPageActions.PATCH_ARTICLE_ERROR:
                    draft.submit.status = LoadStatus.ERROR;
                    draft.submit.error = action.payload;
                    break;

                // Respond to the article page get instead of the response of the patch, because the patch didn't give us all the data.
                case ArticlePageActions.GET_ARTICLE_RESPONSE:
                    draft.submit.status = LoadStatus.SUCCESS;
                    break;
                case EditorPageActions.RESET:
                    return this.initialState;
            }
        });
    };
}
