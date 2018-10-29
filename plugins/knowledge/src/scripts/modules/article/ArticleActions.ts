/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import ReduxActions, { ActionsUnion } from "@library/state/ReduxActions";
import {
    IPatchArticleStatusResponseBody,
    IPatchArticleStatusRequestBody,
    IGetArticleResponseBody,
    IGetArticleRequestBody,
    IGetArticleRevisionsRequestBody,
    IGetArticleRevisionsResponseBody,
    IGetRevisionRequestBody,
    IGetRevisionResponseBody,
} from "@knowledge/@types/api";
import apiv2 from "@library/apiv2";
import ArticleModel from "./ArticleModel";

export interface IArticleActionsProps {
    articleActions: ArticleActions;
}

/**
 * Actions for the article page.
 */
export default class ArticleActions extends ReduxActions {
    public static readonly GET_ARTICLE_REQUEST = "@@article/GET_ARTICLE_REQUEST";
    public static readonly GET_ARTICLE_RESPONSE = "@@article/GET_ARTICLE_RESPONSE";
    public static readonly GET_ARTICLE_ERROR = "@@article/GET_ARTICLE_ERROR";

    public static readonly PATCH_ARTICLE_STATUS_REQUEST = "@@article/PATCH_ARTICLE_STATUS_REQUEST";
    public static readonly PATCH_ARTICLE_STATUS_RESPONSE = "@@article/PATCH_ARTICLE_STATUS_RESPONSE";
    public static readonly PATCH_ARTICLE_STATUS_ERROR = "@@article/PATCH_ARTICLE_STATUS_ERROR";

    public static readonly GET_ARTICLE_REVISIONS_REQUEST = "@@article/GET_ARTICLE_REVISIONS_REQUEST";
    public static readonly GET_ARTICLE_REVISIONS_RESPONSE = "@@article/GET_ARTICLE_REVISIONS_RESPONSE";
    public static readonly GET_ARTICLE_REVISIONS_ERROR = "@@article/GET_ARTICLE_REVISIONS_ERROR";

    public static readonly GET_REVISION_REQUEST = "@@article/GET_REVISION_REQUEST";
    public static readonly GET_REVISION_RESPONSE = "@@article/GET_REVISION_RESPONSE";
    public static readonly GET_REVISION_ERROR = "@@article/GET_REVISION_ERROR";

    /**
     * Union of all possible action types in this class.
     */
    public static readonly ACTION_TYPES:
        | ActionsUnion<typeof ArticleActions.patchArticleStatusACs>
        | ActionsUnion<typeof ArticleActions.getArticleACs>
        | ActionsUnion<typeof ArticleActions.getArticleRevisionsACs>
        | ActionsUnion<typeof ArticleActions.getRevisionACs>;

    /**
     * Static action creators for the get article endpoint.
     */
    private static readonly patchArticleStatusACs = ReduxActions.generateApiActionCreators(
        ArticleActions.PATCH_ARTICLE_STATUS_REQUEST,
        ArticleActions.PATCH_ARTICLE_STATUS_RESPONSE,
        ArticleActions.PATCH_ARTICLE_STATUS_ERROR,
        // https://github.com/Microsoft/TypeScript/issues/10571#issuecomment-345402872
        {} as IPatchArticleStatusResponseBody,
        {} as IPatchArticleStatusRequestBody,
    );

    /**
     * Static action creators for the get article endpoint.
     */
    private static readonly getArticleACs = ReduxActions.generateApiActionCreators(
        ArticleActions.GET_ARTICLE_REQUEST,
        ArticleActions.GET_ARTICLE_RESPONSE,
        ArticleActions.GET_ARTICLE_ERROR,
        // https://github.com/Microsoft/TypeScript/issues/10571#issuecomment-345402872
        {} as IGetArticleResponseBody,
        {} as IGetArticleRequestBody,
    );

    /**
     * Static action creators for the /articles/:id/revisions endpoint.
     */
    private static readonly getArticleRevisionsACs = ReduxActions.generateApiActionCreators(
        ArticleActions.GET_ARTICLE_REVISIONS_REQUEST,
        ArticleActions.GET_ARTICLE_REVISIONS_RESPONSE,
        ArticleActions.GET_ARTICLE_REVISIONS_ERROR,
        // https://github.com/Microsoft/TypeScript/issues/10571#issuecomment-345402872
        {} as IGetArticleRevisionsResponseBody,
        {} as IGetArticleRevisionsRequestBody,
    );

    /**
     * Static action creators for the /articles/:id/revisions endpoint.
     */
    private static readonly getRevisionACs = ReduxActions.generateApiActionCreators(
        ArticleActions.GET_REVISION_REQUEST,
        ArticleActions.GET_REVISION_RESPONSE,
        ArticleActions.GET_REVISION_ERROR,
        // https://github.com/Microsoft/TypeScript/issues/10571#issuecomment-345402872
        {} as IGetRevisionResponseBody,
        {} as IGetRevisionRequestBody,
    );

    public static mapDispatchToProps(dispatch): IArticleActionsProps {
        return {
            articleActions: new ArticleActions(dispatch, apiv2),
        };
    }

    /**
     * Update an articles status.
     *
     * @param articleID The article ID.
     */
    public patchStatus = (body: IPatchArticleStatusRequestBody) => {
        return this.dispatchApi<IPatchArticleStatusResponseBody>(
            "patch",
            `/articles/${body.articleID}/status`,
            ArticleActions.patchArticleStatusACs,
            body,
        );
    };

    /**
     * Get an article by its ID from the API.
     */
    public fetchByID = (options: IGetArticleRequestBody) => {
        return this.dispatchApi<IGetArticleResponseBody>(
            "get",
            `/articles/${options.articleID}?expand=all`,
            ArticleActions.getArticleACs,
            options,
        );
    };

    public fetchRevisionsForArticle = (options: IGetArticleRevisionsRequestBody) => {
        return this.dispatchApi<IGetArticleRevisionsResponseBody>(
            "get",
            `/articles/${options.articleID}/revisions`,
            ArticleActions.getArticleRevisionsACs,
            options,
        );
    };

    public fetchRevisionByID = (options: IGetRevisionRequestBody) => {
        return this.dispatch((c, getState) => {
            const existingRevision = ArticleModel.selectRevision(getState(), options.revisionID);
            if (existingRevision) {
                return this.dispatch(
                    ArticleActions.getRevisionACs.response({ data: existingRevision, status: 200 }, options),
                );
            } else {
                return this.dispatchApi<IGetRevisionResponseBody>(
                    "get",
                    `/article-revisions/${options.revisionID}`,
                    ArticleActions.getRevisionACs,
                    options,
                );
            }
        });
    };
}
