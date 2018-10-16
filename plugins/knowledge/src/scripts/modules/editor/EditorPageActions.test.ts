/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import { expect } from "chai";
import * as sinon from "sinon";
import { createMemoryHistory } from "history";
import EditorPageActions from "@knowledge/modules/editor/EditorPageActions";
import apiv2 from "@library/apiv2";
import MockAdapter from "axios-mock-adapter";
import configureStore, { MockStore } from "redux-mock-store";
import thunk from "redux-thunk";
import { Format } from "@knowledge/@types/api";
import { assertStoreHasActions } from "@library/__tests__/customAssertions";
import ArticlePageActions from "@knowledge/modules/article/ArticlePageActions";
import LocationPickerActions from "@knowledge/modules/locationPicker/LocationPickerActions";
import { IPartialStoreState } from "@knowledge/state/model";
import { LoadStatus } from "@library/@types/api";

describe("EditorPageActions", () => {
    let mockStore: MockStore;
    let mockApi: MockAdapter;
    let editorPageActions: EditorPageActions;

    const initWithState = (state: IPartialStoreState) => {
        const middlewares = [thunk];
        mockStore = configureStore(middlewares)(state);
        editorPageActions = new EditorPageActions(mockStore.dispatch, apiv2);
    };

    before(() => {
        mockApi = new MockAdapter(apiv2);
        initWithState({});
    });

    afterEach(() => {
        mockStore.clearActions();
        mockApi.reset();
    });

    describe("initPageFromLocation", () => {
        it("creates and article and redirects to it", async () => {
            const dummyArticle = { articleID: 1 };
            mockApi.onPost("/api/v2/articles").replyOnce(200, dummyArticle);
            const history = createMemoryHistory();
            history.push("/kb/articles/add");

            void (await editorPageActions.initPageFromLocation(history));

            expect(history.location.pathname).eq("/kb/articles/1/editor");
            assertStoreHasActions(mockStore, [
                {
                    type: EditorPageActions.POST_ARTICLE_REQUEST,
                },
                {
                    type: EditorPageActions.POST_ARTICLE_RESPONSE,
                    payload: {
                        data: dummyArticle,
                    },
                },
            ]);
        });
        it("gets the existing article and sets initializes the location picker from it", async () => {
            const dummyArticle = { articleID: 1, knowledgeCategoryID: 5 };
            mockApi.onGet("/api/v2/articles/1").replyOnce(200, dummyArticle);
            initWithState({
                knowledge: {
                    categories: {
                        status: LoadStatus.SUCCESS,
                        data: {
                            categoriesByID: {
                                5: {
                                    knowledgeCategoryID: 5,
                                    parentID: 1,
                                },
                                1: {
                                    knowledgeCategoryID: 1,
                                },
                            },
                        },
                    },
                },
            });

            void (await editorPageActions.fetchArticleForEdit(1));

            assertStoreHasActions(mockStore, [
                {
                    type: EditorPageActions.GET_ARTICLE_REQUEST,
                },
                {
                    type: EditorPageActions.GET_ARTICLE_RESPONSE,
                    payload: {
                        data: dummyArticle,
                    },
                },
                {
                    type: LocationPickerActions.INIT,
                    payload: {
                        categoryID: 5,
                        parentID: 1,
                    },
                },
            ]);
        });
    });

    describe("updateArticle()", () => {
        it("patches an article, submits a revision and redirects to the page from the response", async () => {
            const dummyRevision = { articleID: 1 };
            const dummyArticle = { url: "/test-redirect-url" };
            mockApi
                .onPatch("/api/v2/articles/1")
                .replyOnce(200, dummyArticle)
                .onPost("/api/v2/article-revisions")
                .replyOnce(200, dummyRevision)
                .onGet("/api/v2/articles/1?expand=all")
                .replyOnce(200, dummyArticle);
            const history = createMemoryHistory();

            void (await editorPageActions.updateArticle(
                { articleID: 1 },
                { name: "test", body: "asd", articleID: 1, format: Format.RICH },
                history,
            ));

            assertStoreHasActions(mockStore, [
                {
                    type: EditorPageActions.PATCH_ARTICLE_REQUEST,
                },
                {
                    type: EditorPageActions.POST_REVISION_REQUEST,
                },
                {
                    type: EditorPageActions.PATCH_ARTICLE_RESPONSE,
                    payload: {
                        data: dummyArticle,
                    },
                },
                {
                    type: EditorPageActions.POST_REVISION_RESPONSE,
                    payload: {
                        data: dummyRevision,
                    },
                },
                {
                    type: ArticlePageActions.GET_ARTICLE_REQUEST,
                },
                {
                    type: ArticlePageActions.GET_ARTICLE_RESPONSE,
                    payload: {
                        data: dummyArticle,
                    },
                },
            ]);

            expect(history.location.pathname).eq(dummyArticle.url);
        });
    });
});
