/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */
import ArticleActions from "@knowledge/modules/article/ArticleActions";
import EditorPageActions from "@knowledge/modules/editor/EditorPageActions";
import { IKnowledgeAppStoreState } from "@knowledge/state/model";
import rootReducer from "@knowledge/state/reducer";
import { getMockKbStore } from "@knowledge/__tests__/kbMockStore";
import {
    dummyArticle,
    dummyDiscussionArticle,
    dummyDraft,
    dummyEditArticle,
    dummyRevision,
} from "@knowledge/__tests__/kbMockTestData";
import apiv2 from "@library/apiv2";
import getStore from "@library/redux/getStore";
import { registerReducer } from "@library/redux/reducerRegistry";
import { _executeReady } from "@library/utility/appUtils";
import { applyAnyFallbackError, mockAPI } from "@library/__tests__/utility";
import { MockStore } from "@vanilla/redux-utils";
import { promiseTimeout } from "@vanilla/utils";
import MockAdapter from "axios-mock-adapter";
import { createMemoryHistory } from "history";
import EditorPageModel, { IEditorPageForm } from "@knowledge/modules/editor/EditorPageModel";
import { DeepPartial } from "redux";
import { LoadStatus } from "@library/@types/api/core";
import NavigationModel from "@knowledge/navigation/state/NavigationModel";
import ArticleModel from "@knowledge/modules/article/ArticleModel";

// Commented until the tests can be re-architected. These are way to fragile in the way they mock their URLs.

describe("EditorPageActions", () => {
    let mockStore: MockStore<IKnowledgeAppStoreState>;
    let mockApi: MockAdapter;
    let editorPageActions: EditorPageActions;
    let mockLocationActions = {
        initLocationPickerFromRecord: jest.fn(),
    };

    const initWithState = (state: DeepPartial<IKnowledgeAppStoreState> = {}) => {
        mockApi = mockAPI();
        mockStore = getMockKbStore(state);
        editorPageActions = new EditorPageActions(mockStore.dispatch, apiv2, mockStore.getState);

        mockLocationActions.initLocationPickerFromRecord.mockReset();
        editorPageActions.setLocationActions(mockLocationActions as any);
    };

    beforeEach(() => initWithState());

    const assertDraftLoaded = (draft: any) => {
        expect(mockStore.isActionTypeDispatched(EditorPageActions.SET_INITIAL_DRAFT)).toEqual(true);
        expect(mockStore.isActionTypeDispatched(ArticleActions.GET_DRAFT_REQUEST)).toEqual(true);
        expect(mockStore.isActionTypeDispatched(ArticleActions.GET_DRAFT_RESPONSE)).toEqual(true);
        expect(mockStore.isActionTypeDispatched(EditorPageActions.UPDATE_FORM)).toEqual(true);

        const updateForm = mockStore.getFirstActionOfType(EditorPageActions.UPDATE_FORM)!;
        expect(updateForm.payload.forceRefresh).toEqual(true);
        expect(updateForm.payload.formData).toEqual({
            ...draft.attributes,
            body: JSON.parse(draft.body),
        });
    };
    describe("initializeAddPage()", () => {
        it("initializes with no params", async () => {
            const history = createMemoryHistory();
            history.push("/kb/articles/add");

            // API responses should throw an error.
            applyAnyFallbackError(mockApi);

            void (await editorPageActions.initializeAddPage(history));
            expect(history.location.pathname).toEqual("/kb/articles/add");
            expect(mockStore.getActions()).toEqual([]);
        });

        it("initializes with a categoryID", async () => {
            const history = createMemoryHistory();
            history.push("/kb/articles/add?knowledgeCategoryID=1");
            void (await editorPageActions.initializeAddPage(history));
            expect(
                mockStore.isActionDispatched({
                    payload: {
                        forceRefresh: true,
                        formData: {
                            knowledgeCategoryID: 1,
                        },
                    },
                    type: EditorPageActions.UPDATE_FORM,
                }),
            );
        });

        it("initializes with a draftID", async () => {
            mockApi.onGet("/articles/drafts/1").replyOnce(200, dummyDraft());

            const history = createMemoryHistory();
            history.push("/kb/articles/add?draftID=1");
            void (await editorPageActions.initializeAddPage(history));

            assertDraftLoaded(dummyDraft());
        });

        it("initializes with both a categoryID and a draftID", async () => {
            mockApi.onGet("/articles/drafts/1").replyOnce(200, dummyDraft());

            const history = createMemoryHistory();
            history.push("/kb/articles/add?draftID=1&knowledgeCategoryID=1");
            void (await editorPageActions.initializeAddPage(history));

            assertDraftLoaded(dummyDraft());
        });

        it("initializes with discussionID", async () => {
            registerReducer("knowledge", rootReducer);
            await _executeReady();
            const store = getStore<IKnowledgeAppStoreState>();
            const pageActions = new EditorPageActions(store.dispatch, apiv2);

            const article = dummyDiscussionArticle();
            const discussionID = 1;

            mockApi
                .onGet("/articles/from-discussion", {
                    params: { discussionID },
                })
                .replyOnce(200, article)
                .onAny()
                .reply(config => {
                    throw new Error("No matching found for URL " + config.url);
                });

            const history = createMemoryHistory();
            history.push("/kb/articles/add?discussionID=" + discussionID);
            await pageActions.initializeAddPage(history);

            // EditorOperationQueue needs some time to finish.
            await promiseTimeout(0);

            const state = store.getState();
            expect(state.knowledge.editorPage.form.discussionID).toEqual(1);
            expect(state.knowledge.editorPage.form.name).toEqual(article.name);
            expect(state.knowledge.editorPage.editorOperationsQueue).toEqual(EditorPageActions.discussionOps(article));
        });
    });

    const mockGetEditAPI = () => {
        mockApi
            .onGet("/articles/1/edit")
            .replyOnce(200, dummyEditArticle())
            .onGet("/articles/1/edit?locale=en")
            .replyOnce(200, dummyEditArticle())
            .onGet("/articles/1", { params: { locale: "en", expand: "all" } })
            .replyOnce(200, dummyArticle());
    };

    describe("initializeEditPage()", () => {
        const testSimpleInitialization = async (fromUrl = "/kb/articles/1/editor") => {
            const history = createMemoryHistory();
            history.push(fromUrl);
            mockGetEditAPI();
            applyAnyFallbackError(mockApi);

            editorPageActions.setLocationActions;

            void (await editorPageActions.initializeEditPage(history, 1));

            expect(mockStore.isActionTypeDispatched(EditorPageActions.GET_ARTICLE_REQUEST)).toEqual(true);
            expect(mockStore.isActionTypeDispatched(EditorPageActions.GET_ARTICLE_RESPONSE)).toEqual(true);
            expect(mockStore.isActionTypeDispatched(EditorPageActions.UPDATE_FORM)).toEqual(true);

            const updateForm = mockStore.getFirstActionOfType(EditorPageActions.UPDATE_FORM)!;
            const { knowledgeCategoryID, name, body: articleRawBody } = dummyEditArticle();
            const body = JSON.parse(articleRawBody);

            expect(updateForm.payload.forceRefresh).toEqual(true);
            expect(updateForm.payload.formData).toEqual({
                body,
                knowledgeCategoryID,
                name,
            });

            expect(mockLocationActions.initLocationPickerFromRecord).toBeCalledTimes(1);
        };

        it("initializes with no params", async () => {
            await testSimpleInitialization();
        });

        const assertArticleAndRevisionLoaded = () => {
            expect(mockStore.isActionTypeDispatched(EditorPageActions.SET_ACTIVE_REVISION)).toEqual(true);
            expect(mockStore.isActionTypeDispatched(ArticleActions.GET_REVISION_REQUEST)).toEqual(true);
            expect(mockStore.isActionTypeDispatched(ArticleActions.GET_REVISION_RESPONSE)).toEqual(true);
            expect(mockStore.isActionTypeDispatched(EditorPageActions.GET_ARTICLE_REQUEST)).toEqual(true);
            expect(mockStore.isActionTypeDispatched(EditorPageActions.GET_ARTICLE_RESPONSE)).toEqual(true);
            expect(mockStore.isActionTypeDispatched(EditorPageActions.UPDATE_FORM)).toEqual(true);

            const updateForm = mockStore.getFirstActionOfType(EditorPageActions.UPDATE_FORM)!;
            const { knowledgeCategoryID } = dummyEditArticle();
            const { name, body } = dummyRevision();

            expect(updateForm.payload.forceRefresh).toEqual(true);
            expect(updateForm.payload.formData).toEqual({
                body: JSON.parse(body),
                knowledgeCategoryID,
                name,
            });
        };

        it("initializes with a revision", async () => {
            const history = createMemoryHistory();
            history.push("/kb/articles/1/editor?articleRevisionID=6");
            mockGetEditAPI();
            const revision = dummyRevision();

            mockApi.onGet(`/article-revisions/${revision.articleRevisionID}`).replyOnce(200, revision);
            applyAnyFallbackError(mockApi);

            void (await editorPageActions.initializeEditPage(history, 1));

            assertArticleAndRevisionLoaded();
        });

        it("ignores a category IDs passed in the query", async () => {
            await testSimpleInitialization("/kb/articles/1/editor?knowledgeCategoryID=532");
        });

        it("ignores kbIDs passed in the query", async () => {
            await testSimpleInitialization("/kb/articles/1/editor?knowledgeBaseID=532");
        });

        it("ignores the article and revision when loading a draft", async () => {
            const history = createMemoryHistory();
            history.push("/kb/articles/1/editor?articleRevisionID=6&draftID=1");
            mockApi.onGet("/articles/drafts/1").replyOnce(200, dummyDraft());

            void (await editorPageActions.initializeEditPage(history, 1));

            expect(mockStore.isActionTypeDispatched(ArticleActions.GET_ARTICLE_REQUEST)).toEqual(false);
            expect(mockStore.isActionTypeDispatched(ArticleActions.GET_REVISION_REQUEST)).toEqual(false);
            assertDraftLoaded(dummyDraft());
        });
    });

    describe("syncDraft()", () => {
        it("can create a new draft", async () => {
            const initialForm: IEditorPageForm = {
                name: "Example Article",
                body: [{ insert: "Test form body" }],
                knowledgeCategoryID: null,
            };

            const initialState: DeepPartial<IKnowledgeAppStoreState> = {
                knowledge: {
                    editorPage: {
                        ...EditorPageModel.INITIAL_STATE,
                        isDirty: true,
                        form: initialForm,
                    },
                },
            };

            initWithState(initialState);
            mockApi.onPost("/articles/drafts").replyOnce(201, dummyDraft());
            const tempID = "TEMP TEMP FOO";

            void (await editorPageActions.syncDraft(tempID));

            expect(mockStore.isActionTypeDispatched(EditorPageActions.SET_INITIAL_DRAFT)).toEqual(true);
            expect(mockStore.isActionTypeDispatched(ArticleActions.POST_DRAFT_REQUEST)).toEqual(true);
            expect(mockStore.isActionTypeDispatched(ArticleActions.POST_DRAFT_RESPONSE)).toEqual(true);

            const initAction = mockStore.getFirstActionOfType(EditorPageActions.SET_INITIAL_DRAFT);
            expect(initAction!.payload.tempID).toEqual(tempID);
        });

        it("can update an existing draft", async () => {
            const draftID = 10;
            const initialForm: IEditorPageForm = {
                name: "Test form name",
                body: [{ insert: "Test form body" }],
                knowledgeCategoryID: null,
            };

            const initialState: DeepPartial<IKnowledgeAppStoreState> = {
                knowledge: {
                    editorPage: {
                        ...EditorPageModel.INITIAL_STATE,
                        isDirty: true,
                        form: initialForm,
                        draft: {
                            data: {
                                draftID,
                            },
                            status: LoadStatus.SUCCESS,
                        },
                    },
                },
            };

            initWithState(initialState);
            mockApi.onPatch(`/articles/drafts/${draftID}`).replyOnce(200, dummyDraft());

            void (await editorPageActions.syncDraft());

            expect(mockStore.isActionTypeDispatched(ArticleActions.PATCH_DRAFT_REQUEST)).toEqual(true);
            expect(mockStore.isActionTypeDispatched(ArticleActions.PATCH_DRAFT_RESPONSE)).toEqual(true);
            expect(mockStore.isActionTypeDispatched(EditorPageActions.SET_INITIAL_DRAFT)).toEqual(false);
        });
    });

    describe("publish()", () => {
        it("can post new article", async () => {
            const history = createMemoryHistory();
            history.push("/kb/articles/add?draftID=1"); // draft ID only used for testing location query string.
            const initialForm: IEditorPageForm = {
                name: "Test form name",
                body: [{ insert: "Test form body" }],
                knowledgeCategoryID: 1,
            };

            const initialState: DeepPartial<IKnowledgeAppStoreState> = {
                knowledge: {
                    editorPage: {
                        ...EditorPageModel.INITIAL_STATE,
                        form: initialForm,
                    },
                },
            };

            initWithState(initialState);
            const article = dummyArticle();

            mockGetEditAPI();
            mockApi
                .onPost(`/articles`)
                .replyOnce(201, article)
                .onGet("/knowledge-bases/1/navigation-flat?locale=en")
                .replyOnce(200, []);
            applyAnyFallbackError(mockApi);
            const pushLocationSpy = jest.fn();
            void (await editorPageActions.publish(history, pushLocationSpy));
            expect(mockStore.isActionTypeDispatched(ArticleActions.POST_ARTICLE_REQUEST)).toEqual(true);
            expect(mockStore.isActionTypeDispatched(ArticleActions.POST_ARTICLE_RESPONSE)).toEqual(true);
            expect(mockStore.isActionTypeDispatched(ArticleActions.PATCH_ARTICLE_REQUEST)).toEqual(false);

            expect(pushLocationSpy.mock.calls[0][0]).toEqual(article.url);

            // Verify query string was removed from previous edit page. Don't want an outdated draft loading.
            const lastPage = history.entries[history.entries.length - 1];
            expect(lastPage.search).toEqual("");
            expect(lastPage.pathname).toEqual(`/kb/articles/${article.articleID}/editor`);
        });

        it("can update existing article", async () => {
            const history = createMemoryHistory();
            history.push("/kb/articles/1/editor?draftID=1"); // draft ID only used for testing location query string.

            const initialForm: IEditorPageForm = {
                name: "Test form name",
                body: [{ insert: "Test form body" }],
                knowledgeCategoryID: 1,
            };

            const article = dummyArticle();

            const initialState: DeepPartial<IKnowledgeAppStoreState> = {
                knowledge: {
                    navigation: NavigationModel.DEFAULT_STATE,

                    editorPage: {
                        ...EditorPageModel.INITIAL_STATE,
                        article: {
                            status: LoadStatus.SUCCESS,
                            data: article,
                        },
                        form: initialForm,
                    },
                    articles: ArticleModel.INITIAL_STATE,
                },
            };

            initWithState(initialState);

            mockGetEditAPI();
            mockApi
                .onPatch(`/articles/${article.articleID}`)
                .replyOnce(200, article)
                .onGet("/knowledge-bases/1/navigation-flat?locale=en")
                .replyOnce(200, []);
            void (await editorPageActions.publish(history, () => {}));

            expect(mockStore.isActionTypeDispatched(ArticleActions.PATCH_ARTICLE_REQUEST)).toEqual(true);
            expect(mockStore.isActionTypeDispatched(ArticleActions.PATCH_ARTICLE_RESPONSE)).toEqual(true);
            expect(mockStore.isActionTypeDispatched(ArticleActions.POST_ARTICLE_REQUEST)).toEqual(false);

            expect(history.location.pathname).toEqual(article.url);

            // Verify query string was removed from previous edit page. Don't want an outdated draft loading.
            const lastPage = history.entries[history.entries.length - 2];
            expect(lastPage.search).toEqual("");
        });

        it("Can publish a new article when the draft's articleID is not available", async () => {
            const history = createMemoryHistory();
            history.push("/kb/articles/432/editor?draftID=1"); // draft ID only used for testing location query string.

            const initialForm: IEditorPageForm = {
                name: "example-article",
                body: [{ insert: "Test form body" }],
                knowledgeCategoryID: null,
            };

            const initialState: DeepPartial<IKnowledgeAppStoreState> = {
                knowledge: {
                    editorPage: {
                        ...EditorPageModel.INITIAL_STATE,
                        form: initialForm,
                        formArticleIDIsDeleted: true,
                    },
                    articles: ArticleModel.INITIAL_STATE,
                },
            };
            initWithState(initialState);

            const article = dummyArticle({ name: "example-article", body: '[{ insert: "Test form body" }]' });

            mockApi
                .onPost(`/articles`)
                .replyOnce(201, article)
                .onGet("/knowledge-bases/1/navigation-flat?locale=en")
                .replyOnce(200, [])
                .onGet("/articles/1", { params: { locale: "en", expand: "all" } })
                .replyOnce(200, article);
            applyAnyFallbackError(mockApi);

            void (await editorPageActions.publish(history, () => {}));

            expect(mockStore.isActionTypeDispatched(ArticleActions.PATCH_ARTICLE_REQUEST)).toEqual(false);
            expect(mockStore.isActionTypeDispatched(ArticleActions.PATCH_ARTICLE_RESPONSE)).toEqual(false);
            expect(mockStore.isActionTypeDispatched(ArticleActions.POST_ARTICLE_RESPONSE)).toEqual(true);

            expect("/kb/articles/1-example-article").toEqual(article.url);
        });
    });
});
