/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */
import { expect } from "chai";
import { DeepPartial } from "redux";
import { IStoreState } from "@knowledge/state/model";
import { LoadStatus } from "@library/@types/api";
import EditorPageModel, { IInjectableEditorProps, IEditorPageState } from "@knowledge/modules/editor/EditorPageModel";
import EditorPageActions from "@knowledge/modules/editor/EditorPageActions";
import ArticleActions from "@knowledge/modules/article/ArticleActions";
import { IArticleDraftAttrs, Format } from "@knowledge/@types/api";

describe("EditorPageModel", () => {
    describe("getInjectableProps()", () => {
        it("can map the injectable props for the loading state", () => {
            const state: DeepPartial<IStoreState> = {
                knowledge: {
                    editorPage: EditorPageModel.INITIAL_STATE,
                },
            };

            const { isDirty, ...expected } = EditorPageModel.INITIAL_STATE;

            expect(EditorPageModel.getInjectableProps(state as any)).deep.eq(expected);
        });
        it("can map the injectable props for the successful state", () => {
            const revision = {
                articleRevisionID: 3,
                bodyRendered: "Hello revision",
            };

            const draft = {
                draftID: 1,
                attributes: {},
                body: [{ insert: "Hello draft" }] as any,
            };

            const mixedState: IEditorPageState = {
                ...EditorPageModel.INITIAL_STATE,
                article: {
                    status: LoadStatus.LOADING,
                },
                draft: {
                    status: LoadStatus.SUCCESS,
                    error: undefined,
                    data: {
                        draftID: draft.draftID,
                    },
                },
                revision: {
                    status: LoadStatus.SUCCESS,
                    error: undefined,
                    data: revision.articleRevisionID,
                },
                form: {
                    name: "test",
                    body: [{ insert: "foo bar" }],
                    knowledgeCategoryID: 29,
                },
                formNeedsRefresh: true,
            };

            const state: DeepPartial<IStoreState> = {
                knowledge: {
                    editorPage: mixedState,
                    articles: {
                        revisionsByID: {
                            [revision.articleRevisionID]: revision,
                        },
                        draftsByID: {
                            [draft.draftID]: draft,
                        },
                    },
                },
            };

            const expected: IInjectableEditorProps = {
                article: {
                    status: LoadStatus.LOADING,
                },
                draft: {
                    status: LoadStatus.SUCCESS,
                    error: undefined,
                    data: draft as any,
                },
                revision: {
                    status: LoadStatus.SUCCESS,
                    error: undefined,
                    data: revision as any,
                },
                saveDraft: {
                    status: LoadStatus.PENDING,
                },
                submit: {
                    status: LoadStatus.PENDING,
                },
                form: {
                    name: "test",
                    body: [{ insert: "foo bar" }],
                    knowledgeCategoryID: 29,
                },
                formNeedsRefresh: true,
            };

            expect(EditorPageModel.getInjectableProps(state as any)).deep.eq(expected);
        });
    });

    describe("reducer()", () => {
        it("can handle tempIDs for drafts", () => {
            const TEMP_ID = "some id";
            const DRAFT_ID = 12;
            const model = new EditorPageModel();
            const draftAttrs: IArticleDraftAttrs = {
                name: "",
                knowledgeCategoryID: 4,
            };
            const initialState = { ...EditorPageModel.INITIAL_STATE };

            // Temp ID gets set
            let state = model.reducer(initialState, EditorPageActions.setInitialDraftAC(undefined, TEMP_ID));
            expect(state.draft.data!.tempID).eq(TEMP_ID);

            // That loading status is tracked on temp id.
            state = model.reducer(
                state,
                ArticleActions.postDraftACs.request({
                    tempID: TEMP_ID,
                    attributes: draftAttrs,
                    body: "[]",
                    format: Format.RICH,
                }),
            );
            expect(state.saveDraft.status).eq(LoadStatus.LOADING);

            // Handle response
            state = model.reducer(
                state,
                ArticleActions.postDraftACs.response({ data: { draftID: DRAFT_ID } } as any, {
                    tempID: TEMP_ID,
                    attributes: draftAttrs,
                    body: "[]",
                    format: Format.RICH,
                }),
            );
            expect(state.saveDraft.status).eq(LoadStatus.SUCCESS);
            expect(state.draft.data!.draftID).eq(DRAFT_ID);
            expect(state.draft.data!.tempID).eq(undefined);
        });
    });
});
