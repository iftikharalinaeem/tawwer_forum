/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import { expect } from "chai";
import * as sinon from "sinon";
import { createMemoryHistory } from "history";
import { thunks, constants } from "@knowledge/modules/editor/state";
import { constants as articleConstants } from "@knowledge/modules/article/state";
import apiv2 from "@library/apiv2";
import MockAdapter from "axios-mock-adapter";
import configureStore, { MockStore } from "redux-mock-store";
import thunk from "redux-thunk";
import { Format } from "@knowledge/@types/api";
import { assertStoreHasActions } from "@library/__tests__/customAssertions";

describe("Editor thunks", () => {
    let mockStore: MockStore;
    let mockApi: MockAdapter;

    before(() => {
        const middlewares = [thunk];
        mockStore = configureStore(middlewares)({});
        mockApi = new MockAdapter(apiv2);
    });

    afterEach(() => {
        mockStore.clearActions();
        mockApi.reset();
    });

    describe("initPageFromLocation", () => {
        it("Does nothing if we are not in the correct location", async () => {
            const history = createMemoryHistory();
            history.push("/test-bad-location");
            const dispatchableAction = thunks.initPageFromLocation(history);
            const dispatch = sinon.spy();
            void (await dispatchableAction(dispatch));
            expect(dispatch.notCalled).eq(true);
            expect(history.location.pathname).eq("/test-bad-location");
        });

        it("creates and article if we are on the /kb/articles/add page and redirects to it", async () => {
            const dummyArticle = { articleID: 1 };
            mockApi.onPost("/api/v2/articles").replyOnce(200, dummyArticle);
            const history = createMemoryHistory();
            history.push("/kb/articles/add");

            const dispatchableAction = thunks.initPageFromLocation(history);
            void (await dispatchableAction(mockStore.dispatch));

            expect(history.location.pathname).eq("/kb/articles/1/editor");
            assertStoreHasActions(mockStore, [
                {
                    type: constants.POST_ARTICLE_REQUEST,
                },
                {
                    type: constants.POST_ARTICLE_RESPONSE,
                    payload: {
                        data: dummyArticle,
                    },
                },
            ]);
        });
        it("gets the existing article if it's id is in the URL", async () => {
            const dummyArticle = { articleID: 1 };
            mockApi.onGet("/api/v2/articles/1").replyOnce(200, dummyArticle);
            const history = createMemoryHistory();
            history.push("/kb/articles/1/editor");

            const dispatchableAction = thunks.initPageFromLocation(history);
            void (await dispatchableAction(mockStore.dispatch));

            assertStoreHasActions(mockStore, [
                {
                    type: constants.GET_ARTICLE_REQUEST,
                },
                {
                    type: constants.GET_ARTICLE_RESPONSE,
                    payload: {
                        data: dummyArticle,
                    },
                },
            ]);
        });
    });

    describe("submitNewRevision()", () => {
        it("submits a revision and redirects to the page from the response", async () => {
            const dummyRevision = { articleID: 1 };
            const dummyArticle = { url: "/test-redirect-url" };
            mockApi
                .onPost("/api/v2/article-revisions")
                .replyOnce(200, dummyRevision)
                .onGet("/api/v2/articles/1?expand=all")
                .replyOnce(200, dummyArticle);
            const history = createMemoryHistory();

            const dispatchableAction = thunks.submitNewRevision(
                { name: "test", body: "asd", articleID: 1, format: Format.RICH },
                history,
            );
            void (await dispatchableAction(mockStore.dispatch));

            assertStoreHasActions(mockStore, [
                {
                    type: constants.POST_REVISION_REQUEST,
                },
                {
                    type: constants.POST_REVISION_RESPONSE,
                    payload: {
                        data: dummyRevision,
                    },
                },
                {
                    type: articleConstants.GET_ARTICLE_REQUEST,
                },
                {
                    type: articleConstants.GET_ARTICLE_RESPONSE,
                    payload: {
                        data: dummyArticle,
                    },
                },
            ]);

            expect(history.location.pathname).eq(dummyArticle.url);
        });
    });
});
