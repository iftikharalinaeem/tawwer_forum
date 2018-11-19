/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */
import { expect } from "chai";
import { createMemoryHistory } from "history";
import ArticleActions from "@knowledge/modules/article/ArticleActions";
import EditorPageActions from "@knowledge/modules/editor/EditorPageActions";
import apiv2 from "@library/apiv2";
import MockAdapter from "axios-mock-adapter";
import { createMockStore, mockStore as MockStore } from "redux-test-utils";
import { IPartialStoreState } from "@knowledge/state/model";

describe("EditorPageActions", () => {
    let mockStore: MockStore<any>;
    let mockApi: MockAdapter;
    let editorPageActions: EditorPageActions;

    before(() => {
        mockApi = new MockAdapter(apiv2);
        initWithState({});
    });

    afterEach(() => {
        initWithState({});
        mockApi.reset();
    });

    const assertDraftLoaded = (draft: any) => {
        expect(mockStore.isActionTypeDispatched(EditorPageActions.SET_INITIAL_DRAFT)).eq(true);
        expect(mockStore.isActionTypeDispatched(ArticleActions.GET_DRAFT_REQUEST)).eq(true);
        expect(mockStore.isActionTypeDispatched(ArticleActions.GET_DRAFT_RESPONSE)).eq(true);
        expect(mockStore.isActionTypeDispatched(EditorPageActions.UPDATE_FORM)).eq(true);

        const updateForm = mockStore.getAction(EditorPageActions.UPDATE_FORM)!;
        expect(updateForm.payload.forceRefresh).eq(true);
        expect(updateForm.payload.formData).deep.eq(draft.attributes);
    };

    const dummyDraft = {
        draftID: 1,
        attributes: {
            name: "foo",
            body: "Hello world.",
        },
    };

    const dummyEditArticle = {
        articleID: 1,
        knowledgeCategoryID: 1,
        sort: null,
        name: "Example Article",
        body: '[{"insert":"Hello world."}]',
        format: "rich",
        locale: "en",
    };

    const initWithState = (state: IPartialStoreState) => {
        mockStore = createMockStore(state);
        editorPageActions = new EditorPageActions(mockStore.dispatch, apiv2);
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
    });

    describe.only("initializeEditPage()", () => {
        it("initializes with no params", async () => {
            const history = createMemoryHistory();
            history.push("/kb/articles/1/editor");
            mockApi.onGet("/api/v2/articles/1/edit").replyOnce(200, dummyEditArticle);

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
        });

        it("initializes with a draft ID", async () => {});

        it("initializes with a revision ID", async () => {});

        it("ignores a category ID", async () => {});

        it("initializes with draft and revision IDs", async () => {});
    });
});
