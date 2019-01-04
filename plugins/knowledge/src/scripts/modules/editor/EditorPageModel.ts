/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { LoadStatus, ILoadable } from "@library/@types/api";
import ReduxReducer from "@library/state/ReduxReducer";
import EditorPageActions from "@knowledge/modules/editor/EditorPageActions";
import produce from "immer";
import { IArticle, IRevision, IResponseArticleDraft } from "@knowledge/@types/api";
import ArticleActions from "../article/ArticleActions";
import { IStoreState } from "@knowledge/state/model";
import ArticleModel from "../article/ArticleModel";
import { DeltaOperation } from "quill/core";
import { createSelector } from "reselect";
import reduceReducers from "reduce-reducers";

export interface IEditorPageForm {
    name: string;
    body: DeltaOperation[];
    knowledgeCategoryID: number | null;
}

export interface IEditorPageState {
    article: ILoadable<IArticle>;
    draft: ILoadable<{
        tempID?: string;
        draftID?: number;
    }>; // The draft ID. Actual draft will live in normalized drafts resource.
    form: IEditorPageForm;
    formNeedsRefresh: boolean;
    revision: ILoadable<number>; // The revision ID. Actual revision will live in normalized revisions resource.
    saveDraft: ILoadable<{}>;
    submit: ILoadable<{}>;
    isDirty: boolean;
}

export interface IInjectableEditorProps {
    article: ILoadable<IArticle>;
    draft: ILoadable<IResponseArticleDraft>;
    form: IEditorPageForm;
    formNeedsRefresh: boolean;
    revision: ILoadable<IRevision>;
    saveDraft: ILoadable<{}>;
    submit: ILoadable<{}>;
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
        const { article, saveDraft, submit, form, formNeedsRefresh } = EditorPageModel.getStateSlice(state);

        return {
            article,
            saveDraft,
            submit,
            form,
            formNeedsRefresh,
            revision: EditorPageModel.selectActiveRevision(state),
            draft: EditorPageModel.selectDraft(state),
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

    private static selectDraftLoadable = (state: IStoreState) => EditorPageModel.getStateSlice(state).draft;
    private static selectDraft = createSelector(
        (state: IStoreState) => state,
        EditorPageModel.selectDraftLoadable,
        (state, draftLoadable) => {
            const { status, error, data } = draftLoadable;
            if (!data || !data.draftID) {
                return { status, error };
            } else {
                const rev = ArticleModel.selectDraft(state, data.draftID);
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
        if (!state.knowledge || !state.knowledge.editorPage) {
            throw new Error(
                "The revision page model has not been wired up properly. Expected to find 'state.knowledge.editorPage'.",
            );
        }

        return state.knowledge.editorPage;
    }

    public static readonly INITIAL_STATE = {
        article: {
            status: LoadStatus.PENDING,
        },
        draft: {
            status: LoadStatus.PENDING,
            error: undefined,
        },
        form: {
            name: "",
            body: [],
            knowledgeCategoryID: null,
        },
        formNeedsRefresh: false,
        revision: {
            status: LoadStatus.PENDING,
            error: undefined,
        },
        saveDraft: {
            status: LoadStatus.PENDING,
        },
        submit: {
            status: LoadStatus.PENDING,
        },
        isDirty: false,
    };
    public initialState: IEditorPageState = EditorPageModel.INITIAL_STATE;

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
                    ...action.payload.formData,
                };
                const { forceRefresh } = action.payload;
                nextState.formNeedsRefresh = forceRefresh;
                nextState.isDirty = !forceRefresh;
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
        if (
            action.meta &&
            action.meta.tempID &&
            nextState.draft.data &&
            action.meta.tempID === nextState.draft.data.tempID
        ) {
            switch (action.type) {
                // Posting a new draft.
                case ArticleActions.POST_DRAFT_REQUEST:
                    nextState.saveDraft.status = LoadStatus.LOADING;
                    break;
                case ArticleActions.POST_DRAFT_RESPONSE:
                    nextState.saveDraft.status = LoadStatus.SUCCESS;
                    nextState.draft.data = { draftID: action.payload.data.draftID };
                    break;
            }
        }

        return nextState;
    };

    private reduceSavedDrafts = (
        nextState: IEditorPageState,
        action: typeof EditorPageActions.ACTION_TYPES | typeof ArticleActions.ACTION_TYPES,
    ): IEditorPageState => {
        // Simple setter.
        if (action.type === EditorPageActions.SET_INITIAL_DRAFT) {
            nextState.draft.data = action.payload;
        }

        // Initial draft handling data handling.
        if (
            action.meta &&
            action.meta.draftID !== null &&
            nextState.draft.data &&
            action.meta.draftID === nextState.draft.data.draftID
        ) {
            switch (action.type) {
                case ArticleActions.GET_DRAFT_REQUEST:
                    nextState.draft.status = LoadStatus.LOADING;
                    break;
                case ArticleActions.PATCH_DRAFT_REQUEST:
                    nextState.saveDraft.status = LoadStatus.LOADING;
                    break;
                case ArticleActions.GET_DRAFT_RESPONSE:
                    nextState.draft.status = LoadStatus.SUCCESS;
                    break;
                case ArticleActions.PATCH_DRAFT_RESPONSE:
                    nextState.saveDraft.status = LoadStatus.SUCCESS;
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
            case ArticleActions.POST_ARTICLE_REQUEST:
                nextState.article.status = LoadStatus.LOADING;
                break;
            case EditorPageActions.GET_ARTICLE_REQUEST:
                nextState.article.status = LoadStatus.LOADING;
                break;
            case EditorPageActions.GET_ARTICLE_RESPONSE:
            case ArticleActions.POST_ARTICLE_RESPONSE:
                nextState.article.status = LoadStatus.SUCCESS;
                nextState.article.data = action.payload.data;
                break;
            case EditorPageActions.GET_ARTICLE_ERROR:
            case ArticleActions.POST_ARTICLE_ERROR:
                nextState.article.status = LoadStatus.ERROR;
                nextState.article.error = action.payload;
                break;
            // Patching the article
            case ArticleActions.PATCH_ARTICLE_REQUEST:
                nextState.submit.status = LoadStatus.LOADING;
                break;
            case ArticleActions.PATCH_ARTICLE_ERROR:
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
