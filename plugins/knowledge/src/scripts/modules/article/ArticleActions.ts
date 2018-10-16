/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import ReduxActions, { ActionsUnion } from "@library/state/ReduxActions";
import { IDeleteArticleResponseBody, IDeleteArticleRequestBody } from "@knowledge/@types/api";
import apiv2 from "@library/apiv2";

export interface IActionsActionsProps {
    articleActions: ArticleActions;
}

/**
 * Actions for the article page.
 */
export default class ArticleActions extends ReduxActions {
    public static readonly DELETE_ARTICLE_REQUEST = "@@article/DELETE_ARTICLE_REQUEST";
    public static readonly DELETE_ARTICLE_RESPONSE = "@@article/DELETE_ARTICLE_RESPONSE";
    public static readonly DELETE_ARTICLE_ERROR = "@@article/DELETE_ARTICLE_ERROR";

    public static readonly RESTORE_ARTICLE_REQUEST = "@@article/RESTORE_ARTICLE_REQUEST";
    public static readonly RESTORE_ARTICLE_RESPONSE = "@@article/RESTORE_ARTICLE_RESPONSE";
    public static readonly RESTORE_ARTICLE_ERROR = "@@article/RESTORE_ARTICLE_ERROR";

    /**
     * Union of all possible action types in this class.
     */
    public static readonly ACTION_TYPES:
        | ActionsUnion<typeof ArticleActions.deleteArticleACs>
        | ActionsUnion<typeof ArticleActions.restorerticleACs>;

    /**
     * Static action creators for the get article endpoint.
     */
    private static readonly deleteArticleACs = ReduxActions.generateApiActionCreators(
        ArticleActions.DELETE_ARTICLE_REQUEST,
        ArticleActions.DELETE_ARTICLE_RESPONSE,
        ArticleActions.DELETE_ARTICLE_ERROR,
        // https://github.com/Microsoft/TypeScript/issues/10571#issuecomment-345402872
        {} as IDeleteArticleResponseBody,
        {} as IDeleteArticleRequestBody,
    );

    /**
     * Static action creators for the get article endpoint.
     */
    private static readonly restorerticleACs = ReduxActions.generateApiActionCreators(
        ArticleActions.RESTORE_ARTICLE_REQUEST,
        ArticleActions.RESTORE_ARTICLE_RESPONSE,
        ArticleActions.RESTORE_ARTICLE_ERROR,
        // https://github.com/Microsoft/TypeScript/issues/10571#issuecomment-345402872
        {} as IDeleteArticleResponseBody,
        {} as IDeleteArticleRequestBody,
    );

    public static mapDispatchToProps(dispatch): IActionsActionsProps {
        return {
            articleActions: new ArticleActions(dispatch, apiv2),
        };
    }

    /**
     * Delete an article.
     *
     * @param articleID The article ID.
     */
    public deleteArticle = (body: IDeleteArticleRequestBody) => {
        return this.dispatchApi<IDeleteArticleResponseBody>(
            "patch",
            `/articles/${body.articleID}/delete`,
            ArticleActions.deleteArticleACs,
            body,
        );
    };

    /**
     * Restore an article
     *
     * @param articleID The article ID.
     */
    public restoreArticle = (body: IDeleteArticleRequestBody) => {
        return this.dispatchApi<IDeleteArticleResponseBody>(
            "patch",
            `/articles/${body.articleID}/undelete`,
            ArticleActions.restorerticleACs,
            body,
        );
    };
}
