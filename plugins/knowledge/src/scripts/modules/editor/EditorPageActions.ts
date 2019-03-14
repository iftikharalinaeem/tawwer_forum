/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import ReduxActions, { ActionsUnion } from "@library/redux/ReduxActions";
import { History } from "history";
import qs from "qs";
import ArticleActions from "@knowledge/modules/article/ArticleActions";
import { IEditorPageForm } from "@knowledge/modules/editor/EditorPageModel";
import { IStoreState } from "@knowledge/state/model";
import { LoadStatus } from "@library/@types/api/core";
import uniqueId from "lodash/uniqueId";
import { EditorRoute } from "@knowledge/routes/pageRoutes";
import isEqual from "lodash/isEqual";
import LocationPickerActions from "@knowledge/modules/locationPicker/LocationPickerActions";
import { KbRecordType } from "@knowledge/navigation/state/NavigationModel";
import { ILocationPickerRecord } from "@knowledge/modules/locationPicker/LocationPickerModel";
import {
    IPostArticleResponseBody,
    IResponseArticleDraft,
    IPostArticleRequestBody,
    IPatchArticleRequestBody,
    IArticle,
    IGetArticleResponseBody,
} from "@knowledge/@types/api/article";
import { Format } from "@knowledge/@types/api/articleRevision";

export default class EditorPageActions extends ReduxActions<IStoreState> {
    // API actions
    public static readonly GET_ARTICLE_REQUEST = "@@articleEditor/GET_EDIT_ARTICLE_REQUEST";
    public static readonly GET_ARTICLE_RESPONSE = "@@articleEditor/GET_EDIT_ARTICLE_RESPONSE";
    public static readonly GET_ARTICLE_ERROR = "@@articleEditor/GET_EDIT_ARTICLE_ERROR";

    // Frontend only actions
    public static readonly RESET = "@@articleEditor/RESET";
    public static readonly SET_ACTIVE_REVISION = "@@articleEditor/SET_ACTIVE_REVISION";

    /**
     * Union of all possible action types in this class.
     */
    public static ACTION_TYPES:
        | ActionsUnion<typeof EditorPageActions.getArticleACs>
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
    public static updateFormAC(formData: Partial<IEditorPageForm>, forceRefresh: boolean = false) {
        return EditorPageActions.createAction(EditorPageActions.UPDATE_FORM, {
            formData,
            forceRefresh,
        });
    }
    public updateForm = this.bindDispatch(EditorPageActions.updateFormAC);

    // Drafts
    public static readonly SET_INITIAL_DRAFT = "@@articleEditor/SET_INITIAL_DRAFT";
    public static setInitialDraftAC(draftID?: number, tempID?: string) {
        return EditorPageActions.createAction(EditorPageActions.SET_INITIAL_DRAFT, {
            draftID,
            tempID,
        });
    }
    public setInitialDraft = this.bindDispatch(EditorPageActions.setInitialDraftAC);

    /** Article page actions instance. */
    private articleActions: ArticleActions = new ArticleActions(this.dispatch, this.api, this.getState);
    private locationActions: LocationPickerActions = new LocationPickerActions(this.dispatch, this.api, this.getState);

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
        const initialKbID = "knowledgeBaseID" ? parseInt(queryParams.knowledgeBaseID, 10) : null;
        const draftLoaded = await this.initializeDraftFromUrl(history);
        if (!draftLoaded && initialCategoryID !== null) {
            this.updateForm({ knowledgeCategoryID: initialCategoryID }, true);
        }

        if (initialCategoryID !== null && initialKbID !== null) {
            await this.locationActions.initLocationPickerFromRecord({
                recordType: KbRecordType.CATEGORY,
                recordID: initialCategoryID,
                knowledgeBaseID: initialKbID,
            });
        }
    }

    private async initializeDraftFromUrl(history: History): Promise<boolean> {
        const queryParams = qs.parse(history.location.search.replace(/^\?/, ""));
        const draftID = "draftID" in queryParams ? parseInt(queryParams.draftID, 10) : null;

        let draftLoaded = false;
        if (draftID !== null) {
            this.dispatch(EditorPageActions.setInitialDraftAC(draftID));
            const draftResponse = await this.articleActions.getDraft({ draftID });
            if (draftResponse) {
                this.pushDraftToForm(draftResponse.data);
                draftLoaded = true;
            }
        }
        return draftLoaded;
    }

    public async initializeEditPage(history: History, articleID: number) {
        const queryParams = qs.parse(history.location.search.replace(/^\?/, ""));

        if (queryParams.articleRevisionID) {
            const revisionID = parseInt(queryParams.articleRevisionID, 10);
            await this.fetchArticleAndRevisionForEdit(history, articleID, revisionID);
        } else {
            await this.fetchArticleForEdit(history, articleID);
        }

        const initialRecord = this.getInitialRecordForEdit();
        if (initialRecord) {
            await this.locationActions.initLocationPickerFromRecord(initialRecord);
        }
    }

    private getInitialRecordForEdit(): ILocationPickerRecord | null {
        const { article, form } = this.getState().knowledge.editorPage;
        const kbID = article.data ? article.data.knowledgeBaseID : null;
        if (kbID == null) {
            return null;
        }

        const categoryID = form.knowledgeCategoryID || null;
        if (categoryID == null) {
            return null;
        }

        return {
            knowledgeBaseID: kbID,
            recordID: categoryID,
            recordType: KbRecordType.CATEGORY,
        };
    }

    private pushDraftToForm(draft: IResponseArticleDraft) {
        const { name, knowledgeCategoryID } = draft.attributes;
        const body = JSON.parse(draft.body);
        this.updateForm({ name, knowledgeCategoryID, body }, true);
    }

    /**
     * Synchronize the current editor draft state to the server.
     */
    public async syncDraft(newDraftID: string = uniqueId()) {
        const state = this.getState();
        const { form, article, draft, isDirty } = state.knowledge.editorPage;

        if (!isDirty) {
            return;
        }

        const recordID = article.data ? article.data.articleID : undefined;

        const { body, ...attrs } = form;
        const contents = {
            body: JSON.stringify(body),
            format: Format.RICH,
        };

        if (draft.data !== undefined && draft.data.draftID !== undefined) {
            await this.articleActions.patchDraft({
                draftID: draft.data.draftID,
                recordID,
                attributes: attrs,
                ...contents,
            });
        } else {
            const tempID = newDraftID;
            this.setInitialDraft(undefined, tempID);
            await this.articleActions.postDraft(
                {
                    recordID,
                    attributes: attrs,
                    ...contents,
                },
                tempID,
            );
        }
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
        const editorState = this.getState().knowledge.editorPage;
        // We don't have an article so go create one.
        const draft = editorState.draft;
        const request: IPostArticleRequestBody = {
            ...editorState.form,
            body: JSON.stringify(editorState.form.body),
            draftID: draft.data ? draft.data.draftID : undefined,
            format: Format.RICH,
        };

        if (editorState.article.status === LoadStatus.SUCCESS && editorState.article.data) {
            const { body: prevBody, name: prevName, knowledgeCategoryID: prevCategoryID } = editorState.article.data;
            const { body, name, knowledgeCategoryID, sort } = request;
            const patchRequest: IPatchArticleRequestBody = {
                articleID: editorState.article.data.articleID,
                body: !isEqual(prevBody, body) ? body : undefined,
                name: prevName !== name ? name : undefined,
                knowledgeCategoryID: prevCategoryID !== knowledgeCategoryID ? knowledgeCategoryID : undefined,
                sort,
            };

            return this.updateArticle(patchRequest, history);
        }

        const response = await this.articleActions.postArticle(request);
        if (!response) {
            return;
        }
        const fullArticleResponse = await this.articleActions.fetchByID({ articleID: response.data.articleID });
        if (!fullArticleResponse) {
            return;
        }

        const article = fullArticleResponse.data;

        // Redirect
        const editLocation = {
            ...history.location,
            pathname: EditorRoute.url({ articleID: article.articleID }),
            search: "",
        };

        history.replace(editLocation);
        history.push({
            pathname: article.url,
        });
    }

    /**
     * Fetch an existing article for editing.
     *
     * @param articleID - The ID of the article to fetch.
     * @param forRevision - Whether or not we're fetching with a revision.
     */
    private async fetchArticleForEdit(history: History, articleID: number, forRevision: boolean = false) {
        // We don't have an article, but we have ID for one. Go get it.
        const [editArticleResponse, articleResponse, draftLoaded] = await Promise.all([
            this.getEditableArticleByID(articleID),
            this.articleActions.fetchByID({ articleID }),
            forRevision ? Promise.resolve(false) : this.initializeDraftFromUrl(history),
        ]);

        // Merge together the two results and re-dispatch with the full data.
        if (editArticleResponse && articleResponse) {
            const article: IArticle = {
                ...articleResponse.data,
                ...editArticleResponse.data,
            };
            editArticleResponse.data = article;

            this.dispatch(EditorPageActions.getArticleACs.response(editArticleResponse));
        }

        if (!draftLoaded && !forRevision && editArticleResponse && editArticleResponse.data) {
            this.updateForm(
                {
                    name: editArticleResponse.data.name,
                    body: JSON.parse(editArticleResponse.data.body),
                    knowledgeCategoryID: editArticleResponse.data.knowledgeCategoryID,
                },
                true,
            );
        }

        return editArticleResponse;
    }

    /**
     * Fetch an existing an article and revision for editing.
     *
     * Useful for restoring a revision.
     *
     * @param articleID - The ID of the article to fetch.
     * @param revision - Start from a particular revision.
     */
    private async fetchArticleAndRevisionForEdit(history: History, articleID: number, revisionID: number) {
        const draftLoaded = await this.initializeDraftFromUrl(history);
        if (draftLoaded) {
            return;
        }

        this.dispatch(EditorPageActions.createSetRevision(revisionID));
        const [article, revision] = await Promise.all([
            this.fetchArticleForEdit(history, articleID, true),
            this.articleActions.fetchRevisionByID({ revisionID }),
        ]);

        if (revision && article) {
            const formData: Partial<IEditorPageForm> = {
                name: revision.data.name,
                body: JSON.parse(revision.data.body),
                knowledgeCategoryID: article.data.knowledgeCategoryID,
            };

            this.updateForm(formData, true);
        }
    }

    /**
     * Submit the editor's form data to the API.
     *
     * @param body - The body of the submit request.
     */
    private async updateArticle(article: IPatchArticleRequestBody, history: History) {
        const response = await this.articleActions.patchArticle(article);
        if (!response) {
            return;
        }
        const fullArticleResponse = await this.articleActions.fetchByID({ articleID: response.data.articleID }, true);
        if (!fullArticleResponse) {
            return;
        }

        const { url } = fullArticleResponse.data;

        // Redirect to the new url.
        history.replace({
            ...history.location,
            search: "",
        });
        history.push(url);
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
