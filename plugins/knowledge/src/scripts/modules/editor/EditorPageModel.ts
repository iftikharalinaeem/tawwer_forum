/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import EditorPageActions from "@knowledge/modules/editor/EditorPageActions";
import { IStoreState, KnowledgeReducer } from "@knowledge/state/model";
import { ILoadable, LoadStatus, IApiError } from "@library/@types/api/core";
import ReduxReducer from "@library/redux/ReduxReducer";
import produce from "immer";
import { DeltaOperation } from "quill/core";
import reduceReducers from "reduce-reducers";
import { createSelector } from "reselect";
import ArticleActions from "@knowledge/modules/article/ArticleActions";
import ArticleModel from "@knowledge/modules/article/ArticleModel";
import { IArticle, IResponseArticleDraft } from "@knowledge/@types/api/article";
import { IRevision } from "@knowledge/@types/api/articleRevision";
import { reducerWithoutInitialState } from "typescript-fsa-reducers";
import { EditorQueueItem } from "@rich-editor/editor/context";
import { boolean } from "@storybook/addon-knobs";

export interface IEditorPageForm {
    name: string;
    body: DeltaOperation[];
    discussionID?: number;
    knowledgeCategoryID: number | null;
    sort?: number;
}

export interface IEditorPageState {
    article: ILoadable<IArticle>;
    draft: ILoadable<{
        tempID?: string;
        draftID?: number;
    }>; // The draft ID. Actual draft will live in normalized drafts resource.
    form: IEditorPageForm;
    formNeedsRefresh: boolean;
    editorOperationsQueue: EditorQueueItem[];
    currentError: IApiError | null;
    revision: ILoadable<number>; // The revision ID. Actual revision will live in normalized revisions resource.
    saveDraft: ILoadable<{}>;
    submit: ILoadable<{}>;
    isDirty: boolean;
    notifyConversion: boolean;
}

export interface IInjectableEditorProps {
    article: ILoadable<IArticle>;
    draft: ILoadable<IResponseArticleDraft>;
    form: IEditorPageForm;
    formNeedsRefresh: boolean;
    editorOperationsQueue: EditorQueueItem[];
    revision: ILoadable<IRevision>;
    saveDraft: ILoadable<{}>;
    submit: ILoadable<{}>;
    notifyConversion: boolean;
    currentError: IApiError | null;
}

type ReducerType = KnowledgeReducer<IEditorPageState>;

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
        const {
            article,
            saveDraft,
            submit,
            form,
            formNeedsRefresh,
            editorOperationsQueue,
            notifyConversion,
            currentError,
        } = EditorPageModel.getStateSlice(state);

        return {
            article,
            saveDraft,
            submit,
            form,
            formNeedsRefresh,
            revision: EditorPageModel.selectActiveRevision(state),
            draft: EditorPageModel.selectDraft(state),
            editorOperationsQueue,
            notifyConversion,
            currentError,
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

    public static readonly INITIAL_STATE: IEditorPageState = {
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
        editorOperationsQueue: [],
        currentError: null,
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
        notifyConversion: false,
    };
    public initialState = EditorPageModel.INITIAL_STATE;

    /**
     * Reducer implementation for the editor page.
     */
    public reducer = (state = this.initialState, action) => {
        return produce(state, nextState => {
            return reduceReducers(
                this.reduceCommon,
                this.reduceSavedDrafts,
                this.reduceInitialDraft,
                this.reduceRevision,
                this.reduceArticle,
                this.reduceEditorQueue,
                this.reduceErrors,
            )(nextState, action);
        });
    };

    /**
     * Simple non-specific reducer for the page.
     */
    private reduceCommon: ReducerType = (nextState = this.initialState, action) => {
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
            case EditorPageActions.RESET_ERROR:
                nextState.currentError = null;
                break;
            case EditorPageActions.RESET:
                return this.initialState;
        }

        return nextState;
    };

    private reduceEditorQueue = reducerWithoutInitialState<IEditorPageState>()
        .case(EditorPageActions.queueEditorOpAC, (nextState, payload) => {
            nextState.editorOperationsQueue.push(payload);
            if (typeof payload === "string") {
                // The item needs conversion.
                nextState.notifyConversion = true;
            }
            return nextState;
        })
        .case(EditorPageActions.clearEditorOpsAC, nextState => {
            nextState.editorOperationsQueue = [];
            return nextState;
        })
        .case(EditorPageActions.clearConversionNoticeAC, nextState => {
            nextState.notifyConversion = false;
            return nextState;
        });

    /**
     * Simple non-specific reducer for the page.
     */
    private reduceErrors: ReducerType = (nextState = this.initialState, action) => {
        switch (action.type) {
            case EditorPageActions.GET_ARTICLE_ERROR:
            case ArticleActions.GET_DRAFT_ERROR:
            case ArticleActions.GET_REVISION_ERROR:
            case ArticleActions.PATCH_DRAFT_ERROR:
            case ArticleActions.PATCH_ARTICLE_ERROR:
            case ArticleActions.POST_ARTICLE_ERROR:
            case ArticleActions.POST_DRAFT_ERROR:
                nextState.currentError = action.payload;
        }

        return nextState;
    };

    private reduceInitialDraft: ReducerType = (nextState = this.initialState, action) => {
        if (
            "meta" in action &&
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

    private reduceSavedDrafts: ReducerType = (nextState = this.initialState, action) => {
        // Simple setter.
        if (action.type === EditorPageActions.SET_INITIAL_DRAFT) {
            nextState.draft.data = action.payload;
        }

        // Initial draft handling data handling.
        if (
            "meta" in action &&
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

    private reduceRevision: ReducerType = (nextState = this.initialState, action) => {
        // Simple setter.
        if (action.type === EditorPageActions.SET_ACTIVE_REVISION) {
            nextState.revision.data = action.payload.revisionID;
        }

        // Normalized handling of revisions.
        if (
            "meta" in action &&
            action.meta &&
            action.meta.revisionID !== null &&
            action.meta.revisionID === nextState.revision.data
        ) {
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

    private reduceArticle: ReducerType = (nextState = this.initialState, action) => {
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
