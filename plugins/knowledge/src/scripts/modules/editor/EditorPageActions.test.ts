/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */
import { IArticle } from "@knowledge/@types/api/article";
import { Format } from "@knowledge/@types/api/articleRevision";
import ArticleActions from "@knowledge/modules/article/ArticleActions";
import ArticleModel from "@knowledge/modules/article/ArticleModel";
import EditorPageActions from "@knowledge/modules/editor/EditorPageActions";
import EditorPageModel, { IEditorPageForm } from "@knowledge/modules/editor/EditorPageModel";
import NavigationModel from "@knowledge/navigation/state/NavigationModel";
import { IPartialStoreState, IStoreState } from "@knowledge/state/model";
import rootReducer from "@knowledge/state/reducer";
import { LoadStatus, PublishStatus } from "@library/@types/api/core";
import apiv2 from "@library/apiv2";
import getStore from "@library/redux/getStore";
import { registerReducer } from "@library/redux/reducerRegistry";
import MockAdapter from "axios-mock-adapter";
import { expect } from "chai";
import { createMemoryHistory } from "history";
import { DeepPartial } from "redux";
import { createMockStore, mockStore as MockStore } from "redux-test-utils";
import { _executeReady } from "@library/utility/appUtils";
import { promiseTimeout } from "@vanilla/utils";

const DEFAULT_KB_STATE = {
    articles: ArticleModel.INITIAL_STATE,
    navigation: NavigationModel.DEFAULT_STATE,
    editorPage: EditorPageModel.INITIAL_STATE,
};

describe("EditorPageActions", () => {
    let mockStore: MockStore<any>;
    let mockApi: MockAdapter;
    let editorPageActions: EditorPageActions;

    beforeEach(() => {
        mockApi = new MockAdapter(apiv2);
        initWithState({
            knowledge: {
                articles: ArticleModel.INITIAL_STATE,
                navigation: NavigationModel.DEFAULT_STATE,
                editorPage: EditorPageModel.INITIAL_STATE,
            },
        });
    });

    const mockGetEditAPI = () => {
        mockApi
            .onGet("/api/v2/articles/1/edit")
            .replyOnce(200, dummyEditArticle)
            .onGet("/api/v2/articles/1?expand=all")
            .replyOnce(200, dummyArticle);
    };

    const assertDraftLoaded = (draft: any) => {
        expect(mockStore.isActionTypeDispatched(EditorPageActions.SET_INITIAL_DRAFT)).eq(true);
        expect(mockStore.isActionTypeDispatched(ArticleActions.GET_DRAFT_REQUEST)).eq(true);
        expect(mockStore.isActionTypeDispatched(ArticleActions.GET_DRAFT_RESPONSE)).eq(true);
        expect(mockStore.isActionTypeDispatched(EditorPageActions.UPDATE_FORM)).eq(true);

        const updateForm = mockStore.getAction(EditorPageActions.UPDATE_FORM)!;
        expect(updateForm.payload.forceRefresh).eq(true);
        expect(updateForm.payload.formData).deep.eq({
            ...draft.attributes,
            body: JSON.parse(draft.body),
        });
    };

    const dummyDraft = {
        draftID: 1,
        attributes: {
            name: "foo",
            knowledgeCategoryID: 1,
        },
        body: `[{"insert": "Hello Draft."}]`,
        format: Format.RICH,
    };

    const dummyArticle: IArticle = {
        articleID: 1,
        knowledgeCategoryID: 4,
        knowledgeBaseID: 1,
        sort: 0,
        seoName: "",
        seoDescription: "",
        slug: "example-article",
        status: PublishStatus.PUBLISHED,
        score: 5,
        countViews: 5,
        outline: [],
        insertUserID: 0,
        updateUserID: 0,
        dateInserted: "",
        dateUpdated: "",
        name: "Example Article",
        body: "<p",
        format: "rich",
        locale: "en",
        url: "/kb/articles/1-example-article",
        reactions: [],
    };

    const dummyDiscussionArticle = {
        discussionID: 1,
        name: "Hello World",
        body: [
            {
                insert: "I am a discussion.\n",
            },
        ],
        format: "Rich",
        url: "/discussion/1/hello-world",
    };

    const dummyEditArticle = {
        articleID: 1,
        knowledgeCategoryID: 4,
        sort: null,
        name: "Example Article",
        body: '[{"insert":"Hello Article."}]',
        format: "rich",
        locale: "en",
    };

    const dummyRevision = {
        articleRevisionID: 6,
        articleID: 1,
        name: "Example Revision",
        body: '[{"insert":"Hello Revision"}]',
    };

    const initWithState = (state: IPartialStoreState) => {
        mockStore = createMockStore(state);
        editorPageActions = new EditorPageActions(mockStore.dispatch, apiv2, () => state);
    };

    describe("initializeAddPage()", () => {
        it("initializes with no params", async () => {
            const history = createMemoryHistory();
            history.push("/kb/articles/add");
            void (await editorPageActions.initializeAddPage(history));
            expect(history.location.pathname).eq("/kb/articles/add");
            expect(mockStore.getActions()).deep.equals([]);
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
            mockApi.onGet("/api/v2/articles/drafts/1").replyOnce(200, dummyDraft);

            const history = createMemoryHistory();
            history.push("/kb/articles/add?draftID=1");
            void (await editorPageActions.initializeAddPage(history));

            assertDraftLoaded(dummyDraft);
        });

        it("initializes with both a categoryID and a draftID", async () => {
            mockApi.onGet("/api/v2/articles/drafts/1").replyOnce(200, dummyDraft);

            const history = createMemoryHistory();
            history.push("/kb/articles/add?draftID=1&knowledgeCategoryID=1");
            void (await editorPageActions.initializeAddPage(history));

            assertDraftLoaded(dummyDraft);
        });

        it("initializes with discussionID", async () => {
            registerReducer("knowledge", rootReducer);
            await _executeReady();
            const store = getStore<IStoreState>();
            const pageActions = new EditorPageActions(store.dispatch, apiv2);

            mockApi
                .onGet("/api/v2/articles/from-discussion", { params: { discussionID: 1 } })
                .replyOnce(200, dummyDiscussionArticle);

            const history = createMemoryHistory();
            history.push("/kb/articles/add?discussionID=1");
            await pageActions.initializeAddPage(history);

            // EditorOperationQueue needs some time to finish.
            await promiseTimeout(0);

            const state = store.getState();
            expect(state.knowledge.editorPage.form.discussionID).eq(dummyDiscussionArticle.discussionID);
            expect(state.knowledge.editorPage.form.name).eq(dummyDiscussionArticle.name);
            expect(state.knowledge.editorPage.editorOperationsQueue).deep.eq(
                EditorPageActions.discussionOps(dummyDiscussionArticle),
            );
        });
    });

    describe("initializeEditPage()", () => {
        const testSimpleInitialization = async (fromUrl = "/kb/articles/1/editor") => {
            const history = createMemoryHistory();
            history.push(fromUrl);
            mockGetEditAPI();

            void (await editorPageActions.initializeEditPage(history, 1));

            expect(mockStore.isActionTypeDispatched(EditorPageActions.GET_ARTICLE_REQUEST)).eq(true);
            expect(mockStore.isActionTypeDispatched(EditorPageActions.GET_ARTICLE_RESPONSE)).eq(true);
            expect(mockStore.isActionTypeDispatched(EditorPageActions.UPDATE_FORM)).eq(true);

            const updateForm = mockStore.getAction(EditorPageActions.UPDATE_FORM)!;
            const { knowledgeCategoryID, name } = dummyEditArticle;
            const body = JSON.parse(dummyEditArticle.body);

            expect(updateForm.payload.forceRefresh).eq(true);
            expect(updateForm.payload.formData).deep.eq({
                body,
                knowledgeCategoryID,
                name,
            });
        };

        it("initializes with no params", async () => {
            await testSimpleInitialization();
        });

        it("initializes with a draft ID", async () => {
            const history = createMemoryHistory();
            history.push("/kb/articles/1/editor?draftID=1");
            mockGetEditAPI();
            mockApi.onGet("/api/v2/articles/drafts/1").replyOnce(200, dummyDraft);

            void (await editorPageActions.initializeEditPage(history, 1));

            expect(
                mockStore.isActionTypeDispatched(EditorPageActions.GET_ARTICLE_REQUEST),
                "Has an article request",
            ).eq(true);
            expect(
                mockStore.isActionTypeDispatched(EditorPageActions.GET_ARTICLE_RESPONSE),
                "Has an article response",
            ).eq(true);

            // Loads only the draft. Doesn't actually use any of the article if we have a draft to load from.
            assertDraftLoaded(dummyDraft);
        });

        const assertArticleAndRevisionLoaded = () => {
            expect(mockStore.isActionTypeDispatched(EditorPageActions.SET_ACTIVE_REVISION)).eq(true);
            expect(mockStore.isActionTypeDispatched(ArticleActions.GET_REVISION_RESPONSE)).eq(true);
            expect(mockStore.isActionTypeDispatched(EditorPageActions.GET_ARTICLE_REQUEST)).eq(true);
            expect(mockStore.isActionTypeDispatched(EditorPageActions.GET_ARTICLE_RESPONSE)).eq(true);
            expect(mockStore.isActionTypeDispatched(EditorPageActions.UPDATE_FORM), "Updates the form").eq(true);

            const updateForm = mockStore.getAction(EditorPageActions.UPDATE_FORM)!;
            const { knowledgeCategoryID } = dummyEditArticle;
            const { name } = dummyRevision;
            const body = JSON.parse(dummyRevision.body);

            expect(updateForm.payload.forceRefresh).eq(true);
            expect(updateForm.payload.formData).deep.eq({
                body,
                knowledgeCategoryID,
                name,
            });
        };

        it("initializes with a revision ID", async () => {
            const history = createMemoryHistory();
            history.push("/kb/articles/1/editor?articleRevisionID=6");
            mockGetEditAPI();
            mockApi.onGet("/api/v2/article-revisions/6").replyOnce(200, dummyRevision);

            initWithState({ knowledge: { ...DEFAULT_KB_STATE, articles: { revisionsByID: {}, articlesByID: {} } } });

            void (await editorPageActions.initializeEditPage(history, 1));

            expect(mockStore.isActionTypeDispatched(ArticleActions.GET_REVISION_REQUEST)).eq(true);
            assertArticleAndRevisionLoaded();
        });

        it("initializes with a revision ID from a cached revision", async () => {
            const history = createMemoryHistory();
            history.push("/kb/articles/1/editor?articleRevisionID=6");
            mockGetEditAPI();

            initWithState({
                knowledge: {
                    ...DEFAULT_KB_STATE,
                    articles: {
                        revisionsByID: {
                            6: dummyRevision,
                        },
                        articlesByID: {},
                    },
                },
            });

            void (await editorPageActions.initializeEditPage(history, 1));

            expect(mockStore.isActionTypeDispatched(ArticleActions.GET_REVISION_REQUEST)).eq(false);
            assertArticleAndRevisionLoaded();
        });

        it("ignores a category ID", async () => {
            await testSimpleInitialization("/kb/articles/1/editor?knowledgeCategoryID=532");
        });

        it("ignores the article and revision when loading a draft", async () => {
            const history = createMemoryHistory();
            history.push("/kb/articles/1/editor?articleRevisionID=6&draftID=1");
            mockApi.onGet("/api/v2/articles/drafts/1").replyOnce(200, dummyDraft);

            void (await editorPageActions.initializeEditPage(history, 1));

            expect(mockStore.isActionTypeDispatched(ArticleActions.GET_ARTICLE_REQUEST)).eq(false);
            expect(mockStore.isActionTypeDispatched(ArticleActions.GET_REVISION_REQUEST)).eq(false);
            assertDraftLoaded(dummyDraft);
        });
    });

    describe("syncDraft()", () => {
        it("can create a new draft", async () => {
            const initialForm: IEditorPageForm = {
                name: "Test form name",
                body: [{ insert: "Test form body" }],
                knowledgeCategoryID: null,
            };

            const initialState: DeepPartial<IStoreState> = {
                knowledge: {
                    editorPage: {
                        ...EditorPageModel.INITIAL_STATE,
                        isDirty: true,
                        form: initialForm,
                    },
                },
            };

            mockApi.onPost("/api/v2/articles/drafts").replyOnce(201, dummyDraft);

            const tempID = "TEMP TEMP FOO";

            initWithState(initialState);
            void (await editorPageActions.syncDraft(tempID));

            expect(
                mockStore.isActionTypeDispatched(EditorPageActions.SET_INITIAL_DRAFT),
                "It sets the initial draft",
            ).eq(true);
            expect(
                mockStore.isActionTypeDispatched(ArticleActions.POST_DRAFT_REQUEST),
                "It makes a request to the server",
            ).eq(true);
            expect(
                mockStore.isActionTypeDispatched(ArticleActions.POST_DRAFT_RESPONSE),
                "It receives a response from the server",
            ).eq(true);

            const initAction = mockStore.getAction(EditorPageActions.SET_INITIAL_DRAFT);
            expect(initAction!.payload.tempID).eq(tempID);
        });

        it("can update an existing draft", async () => {
            const draftID = 10;
            const initialForm: IEditorPageForm = {
                name: "Test form name",
                body: [{ insert: "Test form body" }],
                knowledgeCategoryID: null,
            };

            const initialState: DeepPartial<IStoreState> = {
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

            mockApi.onPatch(`/api/v2/articles/drafts/${draftID}`).replyOnce(200, dummyDraft);

            initWithState(initialState);
            void (await editorPageActions.syncDraft());

            expect(mockStore.isActionTypeDispatched(ArticleActions.PATCH_DRAFT_REQUEST)).eq(true);
            expect(mockStore.isActionTypeDispatched(ArticleActions.PATCH_DRAFT_RESPONSE)).eq(true);
            expect(mockStore.isActionTypeDispatched(EditorPageActions.SET_INITIAL_DRAFT)).eq(false);
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

            const initialState: DeepPartial<IStoreState> = {
                knowledge: {
                    navigation: NavigationModel.DEFAULT_STATE,
                    editorPage: {
                        ...EditorPageModel.INITIAL_STATE,
                        form: initialForm,
                    },
                    articles: ArticleModel.INITIAL_STATE,
                },
            };

            mockApi
                .onPost(`/api/v2/articles`)
                .replyOnce(201, dummyArticle)
                .onGet(`/api/v2/articles/${dummyArticle.articleID}?expand=all`)
                .replyOnce(200, dummyArticle);

            initWithState(initialState);
            void (await editorPageActions.publish(history));

            expect(mockStore.isActionTypeDispatched(ArticleActions.POST_ARTICLE_REQUEST)).eq(true);
            expect(mockStore.isActionTypeDispatched(ArticleActions.POST_ARTICLE_RESPONSE)).eq(true);
            expect(mockStore.isActionTypeDispatched(ArticleActions.PATCH_ARTICLE_REQUEST)).eq(false);

            expect(history.location.pathname).eq(dummyArticle.url);

            // Verify query string was removed from previous edit page. Don't want an outdated draft loading.
            const lastPage = history.entries[history.entries.length - 2];
            expect(lastPage.search).eq("");
            expect(lastPage.pathname).eq(`/kb/articles/${dummyArticle.articleID}/editor`);
        });

        it("can update existing article", async () => {
            const history = createMemoryHistory();
            history.push("/kb/articles/1/editor?draftID=1"); // draft ID only used for testing location query string.

            const initialForm: IEditorPageForm = {
                name: "Test form name",
                body: [{ insert: "Test form body" }],
                knowledgeCategoryID: 1,
            };

            const initialState: DeepPartial<IStoreState> = {
                knowledge: {
                    navigation: NavigationModel.DEFAULT_STATE,

                    editorPage: {
                        ...EditorPageModel.INITIAL_STATE,
                        article: {
                            status: LoadStatus.SUCCESS,
                            data: dummyArticle as any,
                        },
                        form: initialForm,
                    },
                    articles: ArticleModel.INITIAL_STATE,
                },
            };

            mockApi
                .onPatch(`/api/v2/articles/${dummyArticle.articleID}`)
                .replyOnce(200, dummyArticle)
                .onGet(`/api/v2/articles/${dummyArticle.articleID}?expand=all`)
                .replyOnce(200, dummyArticle);

            initWithState(initialState);
            void (await editorPageActions.publish(history));

            expect(mockStore.isActionTypeDispatched(ArticleActions.PATCH_ARTICLE_REQUEST)).eq(true);
            expect(mockStore.isActionTypeDispatched(ArticleActions.PATCH_ARTICLE_RESPONSE)).eq(true);
            expect(mockStore.isActionTypeDispatched(ArticleActions.POST_ARTICLE_REQUEST)).eq(false);

            expect(history.location.pathname).eq(dummyArticle.url);

            // Verify query string was removed from previous edit page. Don't want an outdated draft loading.
            const lastPage = history.entries[history.entries.length - 2];
            expect(lastPage.search).eq("");
        });
    });
});
