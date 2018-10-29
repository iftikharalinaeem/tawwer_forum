/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import ReduxActions, { ActionsUnion } from "@library/state/ReduxActions";
import ArticleActions from "../article/ArticleActions";
import apiv2 from "@library/apiv2";
import RevisionsPageModel from "./RevisionsPageModel";

export interface IInjectableRevisionsPageActions {
    revisionsPageActions: RevisionsPageActions;
}

export default class RevisionsPageActions extends ReduxActions {
    // API actions
    public static readonly SET_ARTICLE = "@@revisionsPage/SET_ARTICLE";
    public static readonly SET_REVISION = "@@revisionsPage/SET_REVISION";
    public static readonly RESET = "@@revisionsPage/RESET";

    public static getInjectableActions(dispatch): IInjectableRevisionsPageActions {
        return {
            revisionsPageActions: new RevisionsPageActions(dispatch, apiv2),
        };
    }

    /**
     * Union of all possible action types in this class.
     */
    public static ACTION_TYPES:
        | ReturnType<typeof RevisionsPageActions.createSetArtAction>
        | ReturnType<typeof RevisionsPageActions.createSetRevAction>
        | ReturnType<typeof RevisionsPageActions.createResetAction>;

    private static createSetArtAction(articleID: number) {
        return RevisionsPageActions.createAction(RevisionsPageActions.SET_ARTICLE, { articleID });
    }

    private static createSetRevAction(revisionID: number) {
        return RevisionsPageActions.createAction(RevisionsPageActions.SET_REVISION, { revisionID });
    }

    /**
     * Create a reset action
     */
    private static createResetAction() {
        return RevisionsPageActions.createAction(RevisionsPageActions.RESET);
    }

    /** Article page actions instance. */
    private articleActions: ArticleActions = new ArticleActions(this.dispatch, this.api);

    public init = (articleID: number, revisionID: number | null = null) => {
        return this.dispatch(async (c, getState) => {
            this.dispatch(RevisionsPageActions.createSetArtAction(articleID));

            const revisions = await this.articleActions.fetchRevisionsForArticle({ articleID });
            if (!revisions || revisions.data.length === 0) {
                return;
            }

            const initialRevisionID =
                revisionID !== null
                    ? revisionID
                    : RevisionsPageModel.selectLatestRevision(getState())!.articleRevisionID;
            this.dispatch(RevisionsPageActions.createSetRevAction(initialRevisionID));
            void this.articleActions.fetchRevisionByID({ revisionID: initialRevisionID });
        });
    };

    public setActiveRevision(revisionID: number | null = null) {
        return this.dispatch(async (c, getState) => {
            if (revisionID) {
                this.dispatch(RevisionsPageActions.createSetRevAction(revisionID));
                void this.articleActions.fetchRevisionByID({ revisionID });
            } else {
                const rev = RevisionsPageModel.selectLatestRevision(getState());
                if (rev) {
                    this.dispatch(RevisionsPageActions.createSetRevAction(rev.articleRevisionID));
                    void this.articleActions.fetchRevisionByID({ revisionID: rev.articleRevisionID });
                }
            }
        });
    }
}
