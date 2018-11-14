/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import { LoadStatus, ILoadable } from "@library/@types/api";
import ReduxReducer from "@library/state/ReduxReducer";
import EditorPageActions from "@knowledge/modules/editor/EditorPageActions";
import produce from "immer";
import {
    IArticle,
    IRevision,
    IKbCategoryFragment,
    Format,
    IResponseArticleDraft,
    IArticleDraft,
} from "@knowledge/@types/api";
import ArticleActions from "../article/ArticleActions";
import { IStoreState } from "@knowledge/state/model";
import ArticleModel from "../article/ArticleModel";
import CategoryModel from "../categories/CategoryModel";
import { DeltaOperation } from "quill/core";
import { createSelector } from "reselect";
import reduceReducers from "reduce-reducers";

export interface IEditorPageForm {
    name: string;
    body: DeltaOperation[];
    format: Format;
    knowledgeCategoryID: number | null;
}

export interface IEditorPageState {
    submit: ILoadable<{}>;
    form: IEditorPageForm;
    revision: ILoadable<number>; // The revision ID. Actual revision will live in normalized revisions resource.
    initialDraft: ILoadable<number>; // The draft ID. Actual draft will live in normalized drafts resource.
    savedDraft: ILoadable<IResponseArticleDraft>;
    article: ILoadable<IArticle>;
    needsDraftConfirmation: boolean;
}

export interface IInjectableEditorProps {
    submit: ILoadable<{}>;
    locationCategory: IKbCategoryFragment | null;
    form: IEditorPageForm;
    article: ILoadable<IArticle>;
    revision: ILoadable<IRevision>;
    initialDraft: ILoadable<IResponseArticleDraft>;
    savedDraft: ILoadable<IResponseArticleDraft>;
    needsDraftConfirmation: boolean;
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
            initialDraft: EditorPageModel.selectInitialDraft(state),
            savedDraft: stateSlice.savedDraft,
            locationCategory,
            form: stateSlice.form,
            needsDraftConfirmation: stateSlice.needsDraftConfirmation,
        };
    }

    private static selectRevisionLoadable = (state: IStoreState) => EditorPageModel.getStateSlice(state).revision;
    private static selectActiveRevision = createSelector(
        (state: IStoreState) => state,
        EditorPageModel.selectRevisionLoadable,
        (state, revLoadable) => {
            const { status, error, data } = revLoadable;
            if (data == null) {
                return { status, error };
            } else {
                const rev = ArticleModel.selectRevision(state, data);
                return {
                    status,
                    error,
                    data: rev,
                } as ILoadable<IRevision>;
            }
        },
    );

    private static selectInitialDraftLoadable = (state: IStoreState) =>
        EditorPageModel.getStateSlice(state).initialDraft;
    private static selectInitialDraft = createSelector(
        (state: IStoreState) => state,
        EditorPageModel.selectInitialDraftLoadable,
        (state, draftLoadable) => {
            const { status, error, data } = draftLoadable;
            if (data == null) {
                return { status, error };
            } else {
                const rev = ArticleModel.selectDraft(state, data);
                return {
                    status,
                    error,
                    data: rev,
                } as ILoadable<IResponseArticleDraft>;
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
        initialDraft: {
            status: LoadStatus.PENDING,
        },
        savedDraft: {
            status: LoadStatus.PENDING,
        },
        revision: {
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
        needsDraftConfirmation: false,
    };

    /**
     * Reducer implementation for the editor page.
     */
    public reducer = (
        state = this.initialState,
        action: typeof EditorPageActions.ACTION_TYPES | typeof ArticleActions.ACTION_TYPES,
    ): IEditorPageState => {
        return produce(state, nextState => {
            return reduceReducers(
                this.reduceCommon,
                this.reduceSavedDrafts,
                this.reduceInitialDraft,
                this.reduceRevision,
                this.reduceArticle,
            )(nextState, action);
        });
    };

    /**
     * Simple non-specific reducer for the page.
     */
    private reduceCommon = (
        nextState: IEditorPageState,
        action: typeof EditorPageActions.ACTION_TYPES | typeof ArticleActions.ACTION_TYPES,
    ): IEditorPageState => {
        switch (action.type) {
            case EditorPageActions.UPDATE_FORM:
                nextState.form = {
                    ...nextState.form,
                    ...action.payload,
                };
                break;
            case EditorPageActions.RESET:
                return this.initialState;
        }

        return nextState;
    };

    private reduceInitialDraft = (
        nextState: IEditorPageState,
        action: typeof EditorPageActions.ACTION_TYPES | typeof ArticleActions.ACTION_TYPES,
    ): IEditorPageState => {
        switch (action.type) {
            // Posting a new draft.
            case ArticleActions.POST_DRAFT_REQUEST:
            case ArticleActions.PATCH_DRAFT_REQUEST:
                nextState.savedDraft.status = LoadStatus.LOADING;
                break;
            case ArticleActions.POST_DRAFT_RESPONSE:
            case ArticleActions.PATCH_DRAFT_RESPONSE:
                nextState.savedDraft.status = LoadStatus.SUCCESS;
                nextState.savedDraft.data = action.payload.data;
                break;
            case ArticleActions.POST_DRAFT_ERROR:
            case ArticleActions.PATCH_DRAFT_ERROR:
                nextState.savedDraft.status = LoadStatus.ERROR;
                nextState.savedDraft.error = action.payload;
                break;
        }

        return nextState;
    };

    private reduceSavedDrafts = (
        nextState: IEditorPageState,
        action: typeof EditorPageActions.ACTION_TYPES | typeof ArticleActions.ACTION_TYPES,
    ): IEditorPageState => {
        // Simple setter.
        if (action.type === EditorPageActions.SET_INITIAL_DRAFT) {
            nextState.initialDraft.data = action.payload.draftID ? action.payload.draftID : undefined;
            nextState.needsDraftConfirmation = action.payload.needsInitialConfirmation;
        }

        // Initial draft handling data handling.
        if (action.meta && action.meta.draftID !== null && action.meta.draftID === nextState.initialDraft.data) {
            switch (action.type) {
                case ArticleActions.GET_DRAFT_REQUEST:
                    nextState.initialDraft.status = LoadStatus.LOADING;
                    break;
                case ArticleActions.GET_DRAFT_RESPONSE:
                    nextState.initialDraft.status = LoadStatus.SUCCESS;
                    break;
            }
        }
        return nextState;
    };

    private reduceRevision = (
        nextState: IEditorPageState,
        action: typeof EditorPageActions.ACTION_TYPES | typeof ArticleActions.ACTION_TYPES,
    ): IEditorPageState => {
        // Simple setter.
        if (action.type === EditorPageActions.SET_ACTIVE_REVISION) {
            nextState.revision.data = action.payload.revisionID;
        }

        // Normalized handling of revisions.
        if (action.meta && action.meta.revisionID !== null && action.meta.revisionID === nextState.revision.data) {
            switch (action.type) {
                case ArticleActions.GET_REVISION_REQUEST:
                    nextState.revision.status = LoadStatus.LOADING;
                    break;
                case ArticleActions.GET_REVISION_RESPONSE:
                    nextState.revision.status = LoadStatus.SUCCESS;
                    break;
                case ArticleActions.GET_REVISION_ERROR:
                    break;
            }
        }

        return nextState;
    };

    private reduceArticle = (
        nextState: IEditorPageState,
        action: typeof EditorPageActions.ACTION_TYPES | typeof ArticleActions.ACTION_TYPES,
    ): IEditorPageState => {
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
            // Respond to the article page get instead of the response of the patch, because the patch didn't give us all the data.
            case ArticleActions.GET_ARTICLE_RESPONSE:
                nextState.submit.status = LoadStatus.SUCCESS;
                break;
        }

        return nextState;
    };
}
