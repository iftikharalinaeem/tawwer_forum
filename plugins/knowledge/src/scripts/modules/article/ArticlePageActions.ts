/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IGetArticleResponseBody } from "@knowledge/@types/api";
import ReduxActions, { ActionsUnion } from "@library/state/ReduxActions";

/**
 * Actions for the article page.
 */
export default class ArticlePageActions extends ReduxActions {
    public static readonly GET_ARTICLE_REQUEST = "@@articlePage/GET_ARTICLE_REQUEST";
    public static readonly GET_ARTICLE_RESPONSE = "@@articlePage/GET_ARTICLE_RESPONSE";
    public static readonly GET_ARTICLE_ERROR = "@@articlePage/GET_ARTICLE_ERROR";
    public static readonly RESET = "@@articlePage/RESET";

    /** Variable representing all of the possible action types from this class. */
    public static readonly ACTION_TYPES:
        | ActionsUnion<typeof ArticlePageActions.getArticleActionCreators>
        | ReturnType<typeof ArticlePageActions.createResetAction>;

    /**
     * Static action creators for the get article endpoint.
     */
    private static readonly getArticleActionCreators = ReduxActions.generateApiActionCreators(
        ArticlePageActions.GET_ARTICLE_REQUEST,
        ArticlePageActions.GET_ARTICLE_RESPONSE,
        ArticlePageActions.GET_ARTICLE_ERROR,
        // https://github.com/Microsoft/TypeScript/issues/10571#issuecomment-345402872
        {} as IGetArticleResponseBody,
        {},
    );

    /**
     * Static action creator for the reset action.
     */
    private static createResetAction() {
        return ReduxActions.createAction(ArticlePageActions.RESET);
    }

    /**
     * Reset the page state.
     */
    public reset = this.bindDispatch(ArticlePageActions.createResetAction);

    /**
     * Get an article by its ID from the API.
     *
     * @param id The article ID.
     */
    public getArticleByID = (id: number) => {
        return this.dispatchApi<IGetArticleResponseBody>(
            "get",
            `/articles/${id}?expand=all`,
            ArticlePageActions.getArticleActionCreators,
            {},
        );
    };
}
