/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import ArticleActions from "@knowledge/modules/article/ArticleActions";
import RevisionsPageModel from "@knowledge/modules/editor/RevisionsPageModel";
import apiv2 from "@library/apiv2";
import ReduxActions from "@library/redux/ReduxActions";
import { getCurrentLocale } from "@vanilla/i18n";

export interface IInjectableRevisionsPageActions {
    revisionsPageActions: RevisionsPageActions;
}

/**
 * Actions for the revisions page.
 */
export default class RevisionsPageActions extends ReduxActions {
    public static readonly IDENTIFIER = "@@articleDrafts/IDENTIFIER";

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
        | ReturnType<typeof RevisionsPageActions.setArticleAC>
        | ReturnType<typeof RevisionsPageActions.setRevisionAC>
        | ReturnType<typeof RevisionsPageActions.createResetAction>;

    /**
     * Action creator for setting the current article.
     */
    private static setArticleAC(articleID: number) {
        return RevisionsPageActions.createAction(RevisionsPageActions.SET_ARTICLE, { articleID });
    }

    /**
     * Action creator for setting the current revision.
     *
     * @param revisionID
     */
    private static setRevisionAC(revisionID: number) {
        return RevisionsPageActions.createAction(RevisionsPageActions.SET_REVISION, { revisionID });
    }

    /**
     * Create a reset action
     */
    private static createResetAction() {
        return RevisionsPageActions.createAction(RevisionsPageActions.RESET);
    }

    /**
     * Reset the page state.
     */
    public reset = this.bindDispatch(RevisionsPageActions.createResetAction);

    /** Article page actions instance. */
    private articleActions: ArticleActions = new ArticleActions(this.dispatch, this.api);

    /**
     * Initialize the revisions page.
     *
     * - Fetch the list of revisions for the current article.
     * - Initialize the active revision and loads it from either  (explicitly passed or takes the latest revision).
     */
    public setActiveArticle = async (articleID: number) => {
        this.dispatch(RevisionsPageActions.setArticleAC(articleID));

        void Promise.all([
            this.articleActions.fetchByID({ articleID, locale: getCurrentLocale() }),
            this.articleActions.getDrafts({ articleID }, RevisionsPageActions.IDENTIFIER),
            this.articleActions.fetchRevisionsForArticle({ articleID }).then(() => this.setActiveRevision()),
        ]);
    };

    /**
     * Set the active revision to display.
     *
     * @param revisionID
     */
    public setActiveRevision = (revisionID: number | null = null) => {
        return this.dispatch((c, getState) => {
            if (revisionID) {
                this.dispatch(RevisionsPageActions.setRevisionAC(revisionID));
                return this.articleActions.fetchRevisionByID({ revisionID });
            } else {
                const activeRevision = RevisionsPageModel.selectActiveRevision(getState());
                if (!activeRevision) {
                    const rev = RevisionsPageModel.selectLatestRevision(getState());
                    if (rev) {
                        this.dispatch(RevisionsPageActions.setRevisionAC(rev.articleRevisionID));
                        return this.articleActions.fetchRevisionByID({ revisionID: rev.articleRevisionID });
                    }
                }
            }
        });
    };
}
