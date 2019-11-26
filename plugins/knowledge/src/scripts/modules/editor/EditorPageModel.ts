/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import EditorPageActions from "@knowledge/modules/editor/EditorPageActions";
import { IKnowledgeAppStoreState, KnowledgeReducer } from "@knowledge/state/model";
import { IApiError, ILoadable, LoadStatus } from "@library/@types/api/core";
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
import isEqual from "lodash/isEqual";
import { t } from "@vanilla/i18n";

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
    formErrors: {
        name: string | null;
        knowledgeCategoryID: string | null;
        body: string | null;
    };
    formArticleIDIsDeleted: boolean;
    formNeedsRefresh: boolean;
    editorOperationsQueue: EditorQueueItem[];
    currentError: IApiError | null;
    revision: ILoadable<number>; // The revision ID. Actual revision will live in normalized revisions resource.
    saveDraft: ILoadable<{}>;
    submit: ILoadable<{}>;
    isDirty: boolean;
    notifyConversion: boolean;
    fallbackLocale: {
        notify: boolean;
        locale: string | null;
    };
    notifyArticleRedirection?: boolean;
}

type ReducerType = KnowledgeReducer<IEditorPageState>;

/**
 * Reducer for the article page.
 */
export default class EditorPageModel extends ReduxReducer<IEditorPageState> {
    private static selectRevisionLoadable = (state: IKnowledgeAppStoreState) =>
        EditorPageModel.getStateSlice(state).revision;
    public static selectActiveRevision = createSelector(
        (state: IKnowledgeAppStoreState) => state,
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

    private static selectDraftLoadable = (state: IKnowledgeAppStoreState) => EditorPageModel.getStateSlice(state).draft;
    public static selectDraft = createSelector(
        (state: IKnowledgeAppStoreState) => state,
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
    public static getStateSlice(state: IKnowledgeAppStoreState): IEditorPageState {
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
            body: [{ insert: "\n" }],
            knowledgeCategoryID: null,
        },
        formErrors: {
            name: null,
            body: null,
            knowledgeCategoryID: null,
        },
        formNeedsRefresh: false,
        formArticleIDIsDeleted: false,
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
        fallbackLocale: {
            notify: false,
            locale: null,
        },
        notifyArticleRedirection: false,
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
                this.reduceNotifications,
                //this.reduceArticleRedirection,
            )(nextState, action);
        });
    };

    /**
     * Simple non-specific reducer for the page.
     */
    private reduceCommon: ReducerType = (nextState = this.initialState, action) => {
        switch (action.type) {
            case EditorPageActions.UPDATE_FORM:
                // Check for changed values.

                const hasChange = (): boolean => {
                    if (action.payload.forceRefresh) {
                        return true;
                    }
                    for (const key of Object.keys(action.payload.formData)) {
                        if (!isEqual(action.payload.formData[key], nextState.form[key])) {
                            return true;
                        }
                    }
                    return false;
                };

                if (!hasChange()) {
                    return nextState;
                }

                nextState.form = {
                    ...nextState.form,
                    ...action.payload.formData,
                };
                const { forceRefresh } = action.payload;
                nextState.formNeedsRefresh = forceRefresh;

                nextState.isDirty = !forceRefresh;

                // Clean up the form errors on the fields that were modified.
                for (const key of Object.keys(action.payload.formData)) {
                    nextState.formErrors[key] = null;
                }
                break;
            case EditorPageActions.RESET_ERROR:
                nextState.currentError = null;
                break;
            case EditorPageActions.RESET:
                return this.initialState;
        }

        return nextState;
    };

    private reduceNotifications = reducerWithoutInitialState<IEditorPageState>()
        .case(EditorPageActions.notifyRedirectionAC, (nextState, payload) => {
            nextState.notifyArticleRedirection = payload.shouldNotify;
            return nextState;
        })
        .case(EditorPageActions.setFallbackLocaleAC, (nextState, payload) => {
            nextState.fallbackLocale.notify = true;
            nextState.fallbackLocale.locale = payload;
            return nextState;
        })
        .case(EditorPageActions.clearFallbackLocaleNoticeAC, nextState => {
            nextState.fallbackLocale.notify = false;
            return nextState;
        });

    private reduceEditorQueue = reducerWithoutInitialState<IEditorPageState>()
        .case(EditorPageActions.queueEditorOpsAC, (nextState, payload) => {
            nextState.editorOperationsQueue = payload;
            payload.forEach(queuedItem => {
                if (typeof queuedItem === "string") {
                    // The item needs conversion.
                    nextState.notifyConversion = true;
                }
            });

            return nextState;
        })
        .case(EditorPageActions.clearEditorOpsAC, nextState => {
            nextState.editorOperationsQueue = [];
            nextState.isDirty = false;
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
            case ArticleActions.GET_DRAFT_ERROR:
            case ArticleActions.PATCH_DRAFT_ERROR:
            case ArticleActions.POST_DRAFT_ERROR:
                nextState.currentError = action.payload;
                break;

            case EditorPageActions.GET_ARTICLE_ERROR:
            case ArticleActions.GET_REVISION_ERROR:
                if (nextState.draft.status !== LoadStatus.PENDING) {
                    // Draft is currently in use for the editor page.
                    // We want to supply a better error message of some of the content
                    // it's based on, it not available anymore.

                    action.payload.message = t("The article this draft is based on is no longer available.");

                    // Clear the categoryID because it may not exist anymore.
                    nextState.formArticleIDIsDeleted = true;
                }

                nextState.currentError = action.payload;
                break;
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
            case EditorPageActions.GET_ARTICLE_REQUEST:
                nextState.article.status = LoadStatus.LOADING;
                break;
            case EditorPageActions.GET_ARTICLE_RESPONSE:
                nextState.article.status = LoadStatus.SUCCESS;
                nextState.article.data = action.payload.data;
                break;
            case EditorPageActions.GET_ARTICLE_ERROR:
                nextState.article.status = LoadStatus.ERROR;
                nextState.article.error = action.payload;
                break;
            // Patching the article
            case ArticleActions.POST_ARTICLE_REQUEST:
            case ArticleActions.PATCH_ARTICLE_REQUEST:
                nextState.submit.status = LoadStatus.LOADING;
                break;
            case ArticleActions.PATCH_ARTICLE_ERROR:
            case ArticleActions.POST_ARTICLE_ERROR:
                nextState.submit.status = LoadStatus.ERROR;

                const responseData = action.payload.response && action.payload.response.data;
                if (!responseData || !responseData.errors) {
                    // No actual error message at all. Let's just use the main error message.
                    nextState.currentError = action.payload;
                } else {
                    // We should have some specific form errors here.
                    for (const [key, value] of Object.entries(responseData.errors)) {
                        // This is a bit of a kludge, but we only designed to show 1 error at at time.
                        nextState.formErrors[key] = value[0].message;
                    }
                }

                nextState.submit.error = action.payload;
                break;
            // Respond to the article page get instead of the response of the patch, because the patch didn't give us all the data.
            case ArticleActions.getArticleACs.done.type:
                nextState.submit.status = LoadStatus.SUCCESS;
                break;
        }

        return nextState;
    };
}
