/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import ReduxActions, { ActionsUnion } from "@library/state/ReduxActions";
import {
    IPostArticleResponseBody,
    IPostArticleRequestBody,
    IGetArticleResponseBody,
    IPatchArticleRequestBody,
    IPatchArticleResponseBody,
    IGetArticleRevisionsResponseBody,
    IGetArticleRevisionsRequestBody,
    IResponseArticleDraft,
} from "@knowledge/@types/api";
import { History } from "history";
import LocationPickerActions from "@knowledge/modules/locationPicker/LocationPickerActions";
import qs from "qs";
import ArticleActions from "../article/ArticleActions";
import { IEditorPageForm, IEditorPageState } from "@knowledge/modules/editor/EditorPageModel";
import { IStoreState } from "@knowledge/state/model";
import { LoadStatus } from "@library/@types/api";
import ArticleModel from "@knowledge/modules/article/ArticleModel";

export default class EditorPageActions extends ReduxActions {
    // API actions
    public static readonly POST_ARTICLE_REQUEST = "@@articleEditor/POST_ARTICLE_REQUEST";
    public static readonly POST_ARTICLE_RESPONSE = "@@articleEditor/POST_ARTICLE_RESPONSE";
    public static readonly POST_ARTICLE_ERROR = "@@articleEditor/POST_ARTICLE_ERROR";

    // API actions
    public static readonly PATCH_ARTICLE_REQUEST = "@@articleEditor/PATCH_ARTICLE_REQUEST";
    public static readonly PATCH_ARTICLE_RESPONSE = "@@articleEditor/PATCH_ARTICLE_RESPONSE";
    public static readonly PATCH_ARTICLE_ERROR = "@@articleEditor/PATCH_ARTICLE_ERROR";

    public static readonly GET_ARTICLE_REQUEST = "@@articleEditor/GET_ARTICLE_REQUEST";
    public static readonly GET_ARTICLE_RESPONSE = "@@articleEditor/GET_ARTICLE_RESPONSE";
    public static readonly GET_ARTICLE_ERROR = "@@articleEditor/GET_ARTICLE_ERROR";

    public static readonly GET_REVISION_REQUEST = "@@articleEditor/GET_REVISION_REQUEST";
    public static readonly GET_REVISION_RESPONSE = "@@articleEditor/GET_REVISION_RESPONSE";
    public static readonly GET_REVISION_ERROR = "@@articleEditor/GET_REVISION_ERROR";

    // Frontend only actions
    public static readonly RESET = "@@articleEditor/RESET";
    public static readonly SET_ACTIVE_REVISION = "@@articleEditor/SET_ACTIVE_REVISION";

    /**
     * Union of all possible action types in this class.
     */
    public static ACTION_TYPES:
        | ActionsUnion<typeof EditorPageActions.postArticleACs>
        | ActionsUnion<typeof EditorPageActions.getRevisionACs>
        | ActionsUnion<typeof EditorPageActions.getArticleACs>
        | ActionsUnion<typeof EditorPageActions.patchArticleACs>
        | ReturnType<typeof EditorPageActions.createSetRevision>
        | ReturnType<typeof EditorPageActions.updateFormAC>
        | ReturnType<typeof EditorPageActions.setInitialDraftAC>
        | ReturnType<typeof EditorPageActions.createResetAction>;

    /**
     * Action creators for GET /articles/:id
     */
    private static getArticleACs = ReduxActions.generateApiActionCreators(
        EditorPageActions.GET_ARTICLE_REQUEST,
        EditorPageActions.GET_ARTICLE_RESPONSE,
        EditorPageActions.GET_ARTICLE_ERROR,
        // https://github.com/Microsoft/TypeScript/issues/10571#issuecomment-345402872
        {} as IPostArticleResponseBody,
        {} as any,
    );

    /**
     * Action creators for POST /articles
     */
    private static postArticleACs = ReduxActions.generateApiActionCreators(
        EditorPageActions.POST_ARTICLE_REQUEST,
        EditorPageActions.POST_ARTICLE_RESPONSE,
        EditorPageActions.POST_ARTICLE_ERROR,
        // https://github.com/Microsoft/TypeScript/issues/10571#issuecomment-345402872
        {} as IPostArticleResponseBody,
        {} as IPostArticleRequestBody,
    );

    /**
     * Action creators for POST /articles
     */
    private static patchArticleACs = ReduxActions.generateApiActionCreators(
        EditorPageActions.PATCH_ARTICLE_REQUEST,
        EditorPageActions.PATCH_ARTICLE_RESPONSE,
        EditorPageActions.PATCH_ARTICLE_ERROR,
        // https://github.com/Microsoft/TypeScript/issues/10571#issuecomment-345402872
        {} as IPatchArticleRequestBody,
        {} as IPatchArticleResponseBody,
    );

    /**
     * Action creators for GET /article-revisions/:id
     */
    private static getRevisionACs = ReduxActions.generateApiActionCreators(
        EditorPageActions.GET_REVISION_REQUEST,
        EditorPageActions.GET_REVISION_RESPONSE,
        EditorPageActions.GET_REVISION_ERROR,
        // https://github.com/Microsoft/TypeScript/issues/10571#issuecomment-345402872
        {} as IGetArticleRevisionsResponseBody,
        {} as IGetArticleRevisionsRequestBody,
    );

    /**
     * Create a reset action
     */
    private static createResetAction() {
        return EditorPageActions.createAction(EditorPageActions.RESET, {});
    }

    private static createSetRevision(revisionID: number) {
        return EditorPageActions.createAction(EditorPageActions.SET_ACTIVE_REVISION, { revisionID });
    }

    /**
     * Reset the page state.
     */
    public reset = this.bindDispatch(EditorPageActions.createResetAction);

    // Form handling
    public static readonly UPDATE_FORM = "@articleEditor/UPDATE_FORM";
    private static updateFormAC(formData: Partial<IEditorPageForm>) {
        return EditorPageActions.createAction(EditorPageActions.UPDATE_FORM, formData);
    }
    public updateForm(formData: Partial<IEditorPageForm>) {
        this.dispatch(EditorPageActions.updateFormAC(formData));
    }

    // Drafts
    public static readonly SET_INITIAL_DRAFT = "@@articleEditor/SET_INITIAL_DRAFT";
    private static setInitialDraftAC(draftID: number | null, needsInitialConfirmation: boolean) {
        return EditorPageActions.createAction(EditorPageActions.SET_INITIAL_DRAFT, {
            draftID,
            needsInitialConfirmation,
        });
    }
    public setInitialDraft = this.bindDispatch(EditorPageActions.setInitialDraftAC);

    /** Article page actions instance. */
    private articleActions: ArticleActions = new ArticleActions(this.dispatch, this.api);

    /** Location picker page actions instance. */
    private locationPickerActions: LocationPickerActions = new LocationPickerActions(this.dispatch, this.api);

    /**
     * Initialize the add page.
     *
     * - Can pull a category ID form a query parameter and intitialize the location.
     * - Can pull a draft ID from a query parameter and intitialize from a draft.
     *
     * @param history - The history for parsing the query string.
     */
    public async initializeAddPage(history: History) {
        const queryParams = qs.parse(history.location.search.replace(/^\?/, ""));
        const initialCategoryID =
            "knowledgeCategoryID" in queryParams ? parseInt(queryParams.knowledgeCategoryID, 10) : null;
        const draftID = "draftID" in queryParams ? parseInt(queryParams.draftID, 10) : null;

        if (draftID !== null) {
            this.dispatch(EditorPageActions.setInitialDraftAC(draftID, false));
            const draft = await this.articleActions.getDraft({ draftID });
            if (draft) {
                this.locationPickerActions.initLocationPickerFromArticle(draft.data.attributes);
            }
        } else {
            if (initialCategoryID !== null) {
                this.locationPickerActions.initLocationPickerFromArticle({ knowledgeCategoryID: initialCategoryID });
            }
        }
    }

    /**
     * Synchronize the current editor draft state to the server.
     */
    public async syncDraft() {
        const state = this.getState<IStoreState>();
        const { form, article } = state.knowledge.editorPage;
        const serializedBody = JSON.stringify(form.body);

        const parentRecordID = form.knowledgeCategoryID !== null ? form.knowledgeCategoryID : undefined;
        const recordID = article.data ? article.data.articleID : undefined;
        const draft = this.getDraft();

        if (draft !== null) {
            await this.articleActions.patchDraft({
                draftID: draft.draftID,
                recordID,
                parentRecordID,
                attributes: {
                    ...form,
                    body: serializedBody,
                },
                body: {
                    bodyContent: serializedBody,
                    bodyFormat: 'rich',
                },
            });
        } else {
            const request = {
                recordID,
                parentRecordID,
                attributes: {
                    ...form,
                    body: serializedBody,
                },
                body: {
                    bodyContent: serializedBody,
                    bodyFormat: 'rich',
                },
            };

            // console.log(request);
            await this.articleActions.postDraft(request);
        }
    }

    /**
     * Get the currently active draft.
     */
    private getDraft(): IResponseArticleDraft | null {
        const { initialDraft, savedDraft } = this.getState<IStoreState>().knowledge.editorPage;
        let draft: IResponseArticleDraft | null = null;
        if (initialDraft.data && initialDraft.status === LoadStatus.SUCCESS) {
            draft = ArticleModel.selectDraft(this.getState(), initialDraft.data);
        } else if (savedDraft.data) {
            draft = savedDraft.data;
        }
        return draft;
    }

    /**
     * Publish the current article/revision to the server.
     *
     * - Cleans up an active draft.
     * - Patches/Posts and article.
     * - Redirects to the url of the new article.
     *
     * @param history History object for redirecting.
     */
    public async publish(history: History) {
        const editorState = this.getState<IStoreState>().knowledge.editorPage;
        // We don't have an article so go create one.
        const draft = this.getDraft();
        const request: IPostArticleRequestBody = {
            ...editorState.form,
            body: JSON.stringify(editorState.form.body),
            draftID: draft ? draft.draftID : undefined,
        };

        if (editorState.article.status === LoadStatus.SUCCESS && editorState.article.data) {
            const patchRequest: IPatchArticleRequestBody = {
                ...request,
                articleID: editorState.article.data.articleID,
            };
            return this.updateArticle(patchRequest, history);
        }

        const response = await this.postArticle(request);
        if (response) {
            const article = response.data;

            // Redirect
            const newLocation = {
                ...history.location,
                pathname: article.url,
                search: "",
            };

            history.replace(newLocation);
        }
    }

    /**
     * Fetch an existing article for editing.
     *
     * @param articleID - The ID of the article to fetch.
     * @param revision - Start from a particular revision.
     */
    public async fetchArticleForEdit(articleID: number, fillForm: boolean = true) {
        // We don't have an article, but we have ID for one. Go get it.
        const [articleResponse, draftsResponse] = await Promise.all([
            this.getEditableArticleByID(articleID),
            this.articleActions.getDrafts({ articleID }),
        ]);

        if (articleResponse && articleResponse.data && fillForm) {
            this.updateForm({
                name: articleResponse.data.name,
                body: JSON.parse(articleResponse.data.body),
                knowledgeCategoryID: articleResponse.data.knowledgeCategoryID,
            });
        }

        if (draftsResponse) {
            const latestDraft = draftsResponse.data.length > 0 ? draftsResponse.data[0] : null;
            if (latestDraft) {
                const { draftID } = latestDraft;
                this.setInitialDraft(draftID, true);
                await this.articleActions.getDraft({ draftID });
            }
        }

        return articleResponse;
    }

    /**
     * Fetch an existing an article and revision for editing.
     *
     * Useful for restoring a revision.
     *
     * @param articleID - The ID of the article to fetch.
     * @param revision - Start from a particular revision.
     */
    public async fetchArticleAndRevisionForEdit(articleID: number, revisionID: number) {
        this.dispatch(EditorPageActions.createSetRevision(revisionID));
        const [article, revision] = await Promise.all([
            this.fetchArticleForEdit(articleID, false),
            this.articleActions.fetchRevisionByID({ revisionID }),
        ]);

        if (revision && article) {
            const formData: Partial<IEditorPageForm> = {
                name: revision.data.name,
                body: JSON.parse(revision.data.body),
                knowledgeCategoryID: article.data.knowledgeCategoryID,
            };

            this.updateForm(formData);
        }
    }

    /**
     * Submit the editor's form data to the API.
     *
     * @param body - The body of the submit request.
     */
    public async updateArticle(article: IPatchArticleRequestBody, history: History) {
        const articleResult = await this.patchArticle(article);
        // Our API request has failed
        if (!articleResult) {
            return;
        }

        const { articleID } = article;
        const newArticle = await this.articleActions.fetchByID({ articleID });
        // Our API request failed.
        if (!newArticle) {
            return;
        }
        const { url } = newArticle.data;

        // Make the URL relative to the root of the site.
        const link = document.createElement("a");
        link.href = url;

        // Redirect to the new url.
        history.push(link.pathname);
    }

    /**
     * Create a new article.
     *
     * @param data The article data.
     */
    private postArticle(data: IPostArticleRequestBody) {
        return this.dispatchApi<IPostArticleResponseBody>("post", `/articles`, EditorPageActions.postArticleACs, data);
    }

    /**
     * Update an article
     */
    private patchArticle(data: IPatchArticleRequestBody) {
        const { articleID, ...rest } = data;
        return this.dispatchApi<IPatchArticleResponseBody>(
            "patch",
            `/articles/${articleID}`,
            EditorPageActions.patchArticleACs,
            rest,
        );
    }

    /**
     * Get an article for editing by its id.
     *
     * @param articleID
     */
    private getEditableArticleByID(articleID: number) {
        return this.dispatchApi<IGetArticleResponseBody>(
            "get",
            `/articles/${articleID}/edit`,
            EditorPageActions.getArticleACs,
            {},
        );
    }
}
