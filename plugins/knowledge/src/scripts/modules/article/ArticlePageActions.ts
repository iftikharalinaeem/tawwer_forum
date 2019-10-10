/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import ReduxActions from "@library/redux/ReduxActions";
import ArticleModel from "@knowledge/modules/article/ArticleModel";
import ArticleActions from "@knowledge/modules/article/ArticleActions";
import NavigationActions from "@knowledge/navigation/state/NavigationActions";

/**
 * Actions for the article page.
 */
export default class ArticlePageActions extends ReduxActions {
    public static readonly INIT = "@@articlePage/INIT";
    public static readonly RESET = "@@articlePage/RESET";

    /**
     * Union of all possible action types in this class.
     */
    public static readonly ACTION_TYPES:
        | ReturnType<typeof ArticlePageActions.createInitAction>
        | ReturnType<typeof ArticlePageActions.createResetAction>;

    private static createInitAction(articleID: number, preloaded: boolean = false) {
        return ReduxActions.createAction(ArticlePageActions.INIT, { articleID, preloaded });
    }

    /**
     * Static action creator for the reset action.
     */
    private static createResetAction() {
        return ReduxActions.createAction(ArticlePageActions.RESET);
    }

    private articleActions = new ArticleActions(this.dispatch, this.api);
    private navigationActions = new NavigationActions(this.dispatch, this.api);

    /**
     * Reset the page state.
     */
    public reset = this.bindDispatch(ArticlePageActions.createResetAction);

    public init = async (articleID: number) => {
        this.dispatch(async (a, getState) => {
            let article = ArticleModel.selectArticle(getState(), articleID);
            if (article) {
                this.dispatch(ArticlePageActions.createInitAction(articleID, true));
            } else {
                this.dispatch(ArticlePageActions.createInitAction(articleID));
                const articleResponse = await this.articleActions.fetchByID({ articleID });
                if (!articleResponse) {
                    return;
                }

                article = articleResponse.data;
            }

            const localResponseData = await this.articleActions.fetchLocales({ articleID });
            const localResponse = localResponseData.data || null;
            /* if (!localResponseData) {
                return localResponseData.data;
            }*/
            const kbID = article.knowledgeBaseID;
            await this.navigationActions.getNavigationFlat(kbID);
            return article;
        });
    };
}
