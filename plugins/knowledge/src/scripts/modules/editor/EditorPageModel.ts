/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import { LoadStatus, ILoadable } from "@library/@types/api";
import ReduxReducer from "@library/state/ReduxReducer";
import EditorPageActions from "@knowledge/modules/editor/EditorPageActions";
import produce from "immer";
import { IArticle, IRevision, IKbCategoryFragment, Format, IArticleDraft } from "@knowledge/@types/api";
import ArticleActions from "../article/ArticleActions";
import { IStoreState } from "@knowledge/state/model";
import ArticleModel from "../article/ArticleModel";
import CategoryModel from "../categories/CategoryModel";
import { DeltaOperation } from "quill/core";
import { createSelector } from "reselect";

export interface IEditorPageForm {
    name: string;
    body: DeltaOperation[];
    format: Format;
    knowledgeCategoryID: number | null;
}

export interface IEditorPageState {
    article: ILoadable<IArticle>;
    draftID: number | null;
    draftStatus: ILoadable<IArticleDraft>;
    revisionID: number | null;
    revisionStatus: ILoadable<IRevision>;
    submit: ILoadable<{}>;
    form: IEditorPageForm;
}

export interface IInjectableEditorProps {
    article: ILoadable<IArticle>;
    revision: ILoadable<IRevision>;
    draft: ILoadable<IArticleDraft>;
    submit: ILoadable<{}>;
    locationCategory: IKbCategoryFragment | null;
    form: IEditorPageForm;
}

/**
 * Reducer for the article page.
 */
export default class EditorPageModel extends ReduxReducer<IEditorPageState> {
    /**
     * Get properties for injection into components.
     *
     * @param state A full state tree.
     */
    public static getInjectableProps(state: IStoreState): IInjectableEditorProps {
        const stateSlice = EditorPageModel.getStateSlice(state);

        let locationCategory: IKbCategoryFragment | null = null;
        const { editorPage, locationPicker } = state.knowledge;
        if (editorPage.article.status === LoadStatus.SUCCESS) {
            locationCategory = CategoryModel.selectKbCategoryFragment(state, locationPicker.chosenCategoryID);
        }

        return {
            article: stateSlice.article,
            submit: stateSlice.submit,
            revision: EditorPageModel.selectActiveRevision(state),
            draft: EditorPageModel.selectActiveDraft(state),
            locationCategory,
            form: stateSlice.form,
        };
    }

    private static selectRevisionID = (state: IStoreState) => EditorPageModel.getStateSlice(state).revisionID;
    private static selectRevisionStatus = (state: IStoreState) => EditorPageModel.getStateSlice(state).revisionStatus;
    private static selectActiveRevision = createSelector(
        (state: IStoreState) => state,
        EditorPageModel.selectRevisionID,
        EditorPageModel.selectRevisionStatus,
        (state, revID, status) => {
            if (revID === null) {
                return status;
            } else {
                const rev = ArticleModel.selectRevision(state, revID);
                return {
                    ...status,
                    data: rev ? rev : undefined,
                };
            }
        },
    );

    private static selectDraftID = (state: IStoreState) => EditorPageModel.getStateSlice(state).draftID;
    private static selectDraftStatus = (state: IStoreState) => EditorPageModel.getStateSlice(state).draftStatus;
    private static selectActiveDraft = createSelector(
        (state: IStoreState) => state,
        EditorPageModel.selectDraftID,
        EditorPageModel.selectDraftStatus,
        (state, draftID, status) => {
            if (draftID === null) {
                return status;
            } else {
                const rev = ArticleModel.selectDraft(state, draftID);
                return {
                    ...status,
                    data: rev ? rev : undefined,
                };
            }
        },
    );

    /**
     * Get the slice of state that this model works with.
     *
     * @param state A full state instance.
     * @throws An error if the state wasn't initialized properly.
     */
    private static getStateSlice(state: IStoreState): IEditorPageState {
        if (!state.knowledge || !state.knowledge.revisionsPage) {
            throw new Error(
                "The revision page model has not been wired up properly. Expected to find 'state.knowledge.revisionsPage'.",
            );
        }

        return state.knowledge.editorPage;
    }

    public initialState: IEditorPageState = {
        article: {
            status: LoadStatus.PENDING,
        },
        draftID: null,
        draftStatus: {
            status: LoadStatus.PENDING,
        },
        revisionID: null,
        revisionStatus: {
            status: LoadStatus.PENDING,
        },
        submit: {
            status: LoadStatus.PENDING,
        },
        form: {
            name: "",
            body: [],
            format: Format.RICH,
            knowledgeCategoryID: null,
        },
    };

    /**
     * Reducer implementation for the editor page.
     */
    public reducer = (
        state = this.initialState,
        action: typeof EditorPageActions.ACTION_TYPES | typeof ArticleActions.ACTION_TYPES,
    ): IEditorPageState => {
        return produce(state, nextState => {
            switch (action.type) {
                case EditorPageActions.POST_ARTICLE_REQUEST:
                    nextState.article.status = LoadStatus.LOADING;
                    break;
                case EditorPageActions.GET_ARTICLE_REQUEST:
                    nextState.article.status = LoadStatus.LOADING;
                    break;
                case EditorPageActions.GET_ARTICLE_RESPONSE:
                case EditorPageActions.POST_ARTICLE_RESPONSE:
                    nextState.article.status = LoadStatus.SUCCESS;
                    nextState.article.data = action.payload.data;
                    break;
                case EditorPageActions.GET_ARTICLE_ERROR:
                case EditorPageActions.POST_ARTICLE_ERROR:
                    nextState.article.status = LoadStatus.ERROR;
                    nextState.article.error = action.payload;
                    break;
                // Patching the article
                case EditorPageActions.PATCH_ARTICLE_REQUEST:
                    nextState.submit.status = LoadStatus.LOADING;
                    break;
                case EditorPageActions.PATCH_ARTICLE_ERROR:
                    nextState.submit.status = LoadStatus.ERROR;
                    nextState.submit.error = action.payload;
                    break;
                // Simple Setters
                case EditorPageActions.SET_ACTIVE_REVISION:
                    nextState.revisionID = action.payload.revisionID;
                    break;
                // Respond to the article page get instead of the response of the patch, because the patch didn't give us all the data.
                case ArticleActions.GET_ARTICLE_RESPONSE:
                    nextState.submit.status = LoadStatus.SUCCESS;
                    break;
                case EditorPageActions.UPDATE_FORM:
                    nextState.form = {
                        ...nextState.form,
                        ...action.payload,
                    };
                    break;
                case EditorPageActions.RESET:
                    return this.initialState;
            }

            if (action.meta && action.meta.revisionID && action.meta.revisionID === nextState.revisionID) {
                switch (action.type) {
                    case ArticleActions.GET_REVISION_REQUEST:
                        nextState.revisionStatus.status = LoadStatus.LOADING;
                        break;
                    case ArticleActions.GET_REVISION_RESPONSE:
                        nextState.revisionStatus.status = LoadStatus.SUCCESS;
                        break;
                    case ArticleActions.GET_REVISION_ERROR:
                        break;
                }
            }
        });
    };
}
