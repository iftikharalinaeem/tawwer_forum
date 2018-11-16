/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */
import { expect } from "chai";
import { createMemoryHistory } from "history";
import EditorPageActions from "@knowledge/modules/editor/EditorPageActions";
import apiv2 from "@library/apiv2";
import MockAdapter from "axios-mock-adapter";
import configureStore, { MockStore } from "redux-mock-store";
import thunk from "redux-thunk";
import { Format } from "@knowledge/@types/api";
import { assertStoreHasActions } from "@library/__tests__/customAssertions";
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

    describe("initializeAddPage()", () => {
        it("initialize the with no params", async () => {
            const history = createMemoryHistory();
            history.push("/kb/articles/add");
            void (await editorPageActions.initializeAddPage(history));
            expect(history.location.pathname).eq("/kb/articles/add");
            expect(mockStore.getActions()).deep.equals([]);
        });
        it("initialize the with a categoryID", async () => {
            const history = createMemoryHistory();
            history.push("/kb/articles/add?knowledgeCategoryID=1");
            void (await editorPageActions.initializeAddPage(history));
            expect(mockStore.getActions()).deep.equals([
                {
                    payload: {
                        forceRefresh: true,
                        formData: {
                            knowledgeCategoryID: 1,
                        },
                    },
                    type: "@articleEditor/UPDATE_FORM",
                },
            ]);
        });
        it("initialize the with a draftID", async () => {
            const dummyDraft = {
                draftID: 1,
                recordType: "article",
                recordID: 1,
                parentRecordID: null,
                attributes: {
                    name: "Dummy Article",
                    body: [
                        {
                            insert: "Hi. I am a dummy article.\n",
                        },
                    ],
                    format: "rich",
                    knowledgeCategoryID: 1,
                },
                insertUserID: 2,
                dateInserted: "2018-12-01T00:00:01+00:00",
                updateUserID: 2,
                dateUpdated: "2018-12-01T00:00:01+00:00",
            };
            mockApi.onGet("/api/v2/articles/drafts/1").replyOnce(200, dummyDraft);

            const history = createMemoryHistory();
            history.push("/kb/articles/add?draftID=1");
            void (await editorPageActions.initializeAddPage(history));
            expect(mockStore.getActions()).deep.equals([]);
        });

        // Add page with both a categoryID and a draftID
    });
});
