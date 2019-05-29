/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */
import { expect } from "chai";
import { LoadStatus } from "@library/@types/api/core";
import EditorPageModel from "@knowledge/modules/editor/EditorPageModel";
import EditorPageActions from "@knowledge/modules/editor/EditorPageActions";
import ArticleActions from "@knowledge/modules/article/ArticleActions";
import { IArticleDraftAttrs } from "@knowledge/@types/api/article";
import { Format } from "@knowledge/@types/api/articleRevision";

describe("EditorPageModel", () => {
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
