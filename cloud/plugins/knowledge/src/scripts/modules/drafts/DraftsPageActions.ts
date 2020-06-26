/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import ReduxActions from "@library/redux/ReduxActions";
import ArticleActions from "@knowledge/modules/article/ArticleActions";
import { IKnowledgeAppStoreState } from "@knowledge/state/model";

export default class DraftsPageActions extends ReduxActions<IKnowledgeAppStoreState> {
    public static readonly IDENTIFIER = "@@articleDrafts/IDENTIFIER";

    public static readonly RESET = "@@articleDrafts/RESET";

    public static ACTION_TYPES: ReturnType<typeof DraftsPageActions.resetAC>;

    private articleActions = new ArticleActions(this.dispatch, this.api, this.getState);

    /**
     * Get drafts created by a specific user.
     */
    public async getCurrentUserDrafts() {
        const state = this.getState();
        const currentUserID = state.users.current.data!.userID;
        return this.articleActions.getDrafts({ insertUserID: currentUserID }, DraftsPageActions.IDENTIFIER);
    }

    /**
     * Create a reset action
     */
    private static resetAC() {
        return DraftsPageActions.createAction(DraftsPageActions.RESET, {});
    }

    /**
     * Reset the page state.
     */
    public reset = this.bindDispatch(DraftsPageActions.resetAC);
}
