/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import {
    IArticle,
    IGetArticleFromDiscussionResponse,
    IGetArticleResponseBody,
    IPatchArticleRequestBody,
    IPostArticleRequestBody,
    IPostArticleResponseBody,
    IResponseArticleDraft,
} from "@knowledge/@types/api/article";
import { Format } from "@knowledge/@types/api/articleRevision";
import ArticleActions from "@knowledge/modules/article/ArticleActions";
import { IEditorPageForm } from "@knowledge/modules/editor/EditorPageModel";
import LocationPickerActions from "@knowledge/modules/locationPicker/LocationPickerActions";
import { ILocationPickerRecord } from "@knowledge/modules/locationPicker/LocationPickerModel";
import { KbRecordType } from "@knowledge/navigation/state/NavigationModel";
import { EditorRoute } from "@knowledge/routes/pageRoutes";
import { IKnowledgeAppStoreState } from "@knowledge/state/model";
import { LoadStatus } from "@library/@types/api/core";
import ReduxActions, { ActionsUnion } from "@library/redux/ReduxActions";
import { formatUrl } from "@library/utility/appUtils";
import { History } from "history";
import isEqual from "lodash/isEqual";
import uniqueId from "lodash/uniqueId";
import qs from "qs";
import actionCreatorFactory from "typescript-fsa";
import { EditorQueueItem } from "@rich-editor/editor/context";
import { EditorPage } from "@knowledge/modules/editor/EditorPage";
import { getRelativeUrl } from "@library/utility/appUtils";
import { article } from "@knowledge/navigation/navigationManagerIcons";

const createAction = actionCreatorFactory("@@articleEditor");

export default class EditorPageActions extends ReduxActions<IKnowledgeAppStoreState> {
    // API actions
    public static readonly GET_ARTICLE_REQUEST = "@@articleEditor/GET_EDIT_ARTICLE_REQUEST";
    public static readonly GET_ARTICLE_RESPONSE = "@@articleEditor/GET_EDIT_ARTICLE_RESPONSE";
    public static readonly GET_ARTICLE_ERROR = "@@articleEditor/GET_EDIT_ARTICLE_ERROR";

    // Frontend only actions
    public static readonly RESET = "@@articleEditor/RESET";
    public static readonly RESET_ERROR = "@@articleEditor/RESET_ERROR";
    public static readonly SET_ACTIVE_REVISION = "@@articleEditor/SET_ACTIVE_REVISION";

    /**
     * Union of all possible action types in this class.
     */
    public static ACTION_TYPES:
        | ActionsUnion<typeof EditorPageActions.getArticleACs>
        | ReturnType<typeof EditorPageActions.createSetRevision>
        | ReturnType<typeof EditorPageActions.updateFormAC>
        | ReturnType<typeof EditorPageActions.setInitialDraftAC>
        | ReturnType<typeof EditorPageActions.createResetAction>
        | ReturnType<typeof EditorPageActions.createResetErrorAction>;

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

    /**
     * Create a reset error action
     */
    private static createResetErrorAction() {
        return EditorPageActions.createAction(EditorPageActions.RESET_ERROR, {});
    }

    private static createSetRevision(revisionID: number) {
        return EditorPageActions.createAction(EditorPageActions.SET_ACTIVE_REVISION, { revisionID });
    }

    /**
     * Reset the page state.
     */
    public reset = this.bindDispatch(EditorPageActions.createResetAction);

    /**
     * Reset the page state.
     */
    public resetError = this.bindDispatch(EditorPageActions.createResetErrorAction);

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
        if (!draftLoaded) {
            await this.initializeDiscussionFromUrl(history);
            if (initialCategoryID !== null) {
                this.updateForm({ knowledgeCategoryID: initialCategoryID }, true);
            }
        }

        if (initialCategoryID !== null && initialKbID !== null) {
            await this.locationActions.initLocationPickerFromRecord(
                {
                    recordType: KbRecordType.CATEGORY,
                    recordID: initialCategoryID,
                    knowledgeBaseID: initialKbID,
                },
                null,
            );
        }
    }

    private async initializeDiscussionFromUrl(history: History): Promise<boolean> {
        const queryParams = qs.parse(history.location.search.replace(/^\?/, ""));
        const discussionID = "discussionID" in queryParams ? parseInt(queryParams.discussionID, 10) : null;

        let loaded = false;
        if (discussionID !== null) {
            const response = await this.articleActions.getFromDiscussion({ discussionID });
            if (response) {
                this.pushDiscussionToForm(discussionID, response);
                loaded = true;
            }
        }
        return loaded;
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
            await this.locationActions.initLocationPickerFromRecord(initialRecord, this.getCurrentArticle());
        }
    }

    private getInitialRecordForEdit(): ILocationPickerRecord | null {
        const editorPage = this.getState().knowledge.editorPage;
        if (!editorPage) {
            // Possible in testing with a mock store.
            return null;
        }
        const { article, form } = editorPage;
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

    private getCurrentArticle(): IArticle | null {
        const { article } = this.getState().knowledge.editorPage;

        return article.data || null;
    }

    public static queueEditorOpsAC = createAction<EditorQueueItem[]>("QUEUE_EDITOR_OP");
    public queueEditorOps = this.bindDispatch(EditorPageActions.queueEditorOpsAC);

    public static clearEditorOpsAC = createAction("CLEAR_EDITOR_OPS");
    public clearEditorOps = this.bindDispatch(EditorPageActions.clearEditorOpsAC);

    public static clearConversionNoticeAC = createAction("CLEAR_CONVERSION_NOTICE");
    public clearConversionNotice = this.bindDispatch(EditorPageActions.clearConversionNoticeAC);

    public static discussionOps(discussion: IGetArticleFromDiscussionResponse) {
        return [
            [
                { attributes: { italic: true }, insert: "This article was created from a " },
                { attributes: { italic: true, link: formatUrl(discussion.url) }, insert: "community discussion" },
                { attributes: { italic: true }, insert: "." },
                { insert: "\n" },
            ],
            // Add the discussion content.
            discussion.body,
        ];
    }

    private pushDiscussionToForm(discussionID: number, discussion: IGetArticleFromDiscussionResponse) {
        const { name } = discussion;

        // Set the title of the article.
        this.updateForm({ name }, true);

        const queuedOps = EditorPageActions.discussionOps(discussion);

        // If we have any answers, add those too.
        if (discussion.acceptedAnswers) {
            queuedOps.push([
                { insert: discussion.acceptedAnswers.length > 1 ? "Answers" : "Answer" },
                { attributes: { header: { level: 2, ref: "answer" } }, insert: "\n" },
            ]);

            discussion.acceptedAnswers.forEach(answer => {
                queuedOps.push(answer.body);
            });
        }

        // Add the "created from" text.
        this.queueEditorOps(queuedOps);
        this.updateForm({ discussionID });
    }

    private pushDraftToForm(draft: IResponseArticleDraft) {
        const { discussionID, name, knowledgeCategoryID } = draft.attributes;
        const body = JSON.parse(draft.body);

        const formData: Partial<IEditorPageForm> = { name, knowledgeCategoryID, body };
        if (discussionID) {
            formData.discussionID = discussionID;
        }
        this.updateForm(formData, true);
    }

    /**
     * Synchronize the current editor draft state to the server.
     */
    public syncDraft = async (newDraftID: string = uniqueId()) => {
        const state = this.getState();
        const { form, article, draft, isDirty, notifyConversion } = state.knowledge.editorPage;

        if (!isDirty || notifyConversion) {
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
    };

    /**
     * Publish the current article/revision to the server.
     *
     * - Cleans up an active draft.
     * - Patches/Posts and article.
     * - Redirects to the url of the new article.
     *
     * @param history History object for redirecting.
     */
    public publish = async (history: History) => {
        const editorState = this.getState().knowledge.editorPage;
        // We don't have an article so go create one.
        const isBodyEmpty = isEqual(editorState.form.body, [{ insert: "\n" }]) || isEqual(editorState.form.body, []);
        const draft = editorState.draft;
        const request: IPostArticleRequestBody = {
            ...editorState.form,
            body: isBodyEmpty ? "" : JSON.stringify(editorState.form.body),
            draftID: draft.data ? draft.data.draftID : undefined,
            format: Format.RICH,
        };

        if (editorState.article.status === LoadStatus.SUCCESS && editorState.article.data) {
            const { body: prevBody, name: prevName, knowledgeCategoryID: prevCategoryID } = editorState.article.data;
            const { body, name, knowledgeCategoryID, sort } = request;

            // We only want to submit the body if it is not the default value.
            const shouldSubmitBody = prevBody !== body;

            const patchRequest: IPatchArticleRequestBody = {
                articleID: editorState.article.data.articleID,
                body: shouldSubmitBody ? body : undefined,
                format: shouldSubmitBody ? Format.RICH : undefined, // forced to always be rich on insert
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

        const pathname = getRelativeUrl(article.url);
        history.push({
            pathname,
        });
    };

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
        if (!editArticleResponse || !articleResponse) {
            return;
        }
        const article: IArticle = {
            ...articleResponse.data,
            ...editArticleResponse.data,
        };
        editArticleResponse.data = article;

        this.dispatch(EditorPageActions.getArticleACs.response(editArticleResponse));

        if (!draftLoaded && !forRevision && editArticleResponse && editArticleResponse.data) {
            const newFormValue: Partial<IEditorPageForm> = {
                name: editArticleResponse.data.name,
                knowledgeCategoryID: editArticleResponse.data.knowledgeCategoryID,
            };

            const { body, format } = editArticleResponse.data;
            // Check if we have another format loaded. If we do we need to use the queue.
            if (format.toLowerCase() === "rich") {
                newFormValue.body = JSON.parse(body);
            } else {
                newFormValue.body = [];
                const renderedBody = articleResponse.data.body;
                this.queueEditorOps([renderedBody]);
            }

            this.updateForm(newFormValue, true);
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

        const pathname = getRelativeUrl(fullArticleResponse.data.url); //const { pathname } = new URL(article.url)

        // Redirect to the new url.
        history.replace({
            ...history.location,
            search: "",
        });
        history.push(pathname);
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
