/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import ReduxActions from "@library/redux/ReduxActions";
import ArticleModel from "@knowledge/modules/article/ArticleModel";
import ArticleActions from "@knowledge/modules/article/ArticleActions";
import NavigationActions from "@knowledge/navigation/state/NavigationActions";
import { IKnowledgeAppStoreState } from "@knowledge/state/model";
import { getCurrentLocale } from "@vanilla/i18n";
import actionCreatorFactory from "typescript-fsa";
import { IArticle } from "@knowledge/@types/api/article";

const createAction = actionCreatorFactory("@@articlePage");

/**
 * Actions for the article page.
 */
export default class ArticlePageActions extends ReduxActions<IKnowledgeAppStoreState> {
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

    private takeArticleFromCacheOrRequest = async (articleID: number): Promise<IArticle | undefined> => {
        let article = ArticleModel.selectArticle(this.getState(), articleID);
        if (article) {
            this.dispatch(ArticlePageActions.createInitAction(articleID, true));
        } else {
            this.dispatch(ArticlePageActions.createInitAction(articleID));
            const article = await this.articleActions.fetchByID({
                locale: getCurrentLocale(),
                articleID,
            });
            return article;
        }
    };

    private loadArticlesInThisKnowledgeCategory = async id => {
        await this.articleActions.getArticleList(
            {
                knowledgeCategoryID: id,
                locale: getCurrentLocale(),
            },
            false,
            true,
        );
    };

    public init = async (articleID: number) => {
        const promises = [
            await this.takeArticleFromCacheOrRequest(articleID).then(article => {
                if (article) {
                    const kbID = article.knowledgeBaseID;
                    void this.navigationActions.getNavigationFlat(kbID);
                    this.loadArticlesInThisKnowledgeCategory(article?.knowledgeCategoryID);
                }
                return article;
            }),
            this.articleActions.fetchLocales({ articleID }),
            this.articleActions.getRelatedArticles({
                articleID: articleID,
                locale: getCurrentLocale(),
            }),
        ];

        const [article] = await Promise.all(promises);

        return article;
    };
}
