/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import ReduxActions, { ActionsUnion } from "@library/state/ReduxActions";
import { IPatchArticleStatusResponseBody, IPatchArticleStatusRequestBody } from "@knowledge/@types/api";
import apiv2 from "@library/apiv2";

export interface IArticleActionsProps {
    articleActions: ArticleActions;
}

/**
 * Actions for the article page.
 */
export default class ArticleActions extends ReduxActions {
    public static readonly PATCH_ARTICLE_STATUS_REQUEST = "@@article/PATCH_ARTICLE_STATUS_REQUEST";
    public static readonly PATCH_ARTICLE_STATUS_RESPONSE = "@@article/PATCH_ARTICLE_STATUS_RESPONSE";
    public static readonly PATCH_ARTICLE_STATUS_ERROR = "@@article/PATCH_ARTICLE_STATUS_ERROR";

    /**
     * Union of all possible action types in this class.
     */
    public static readonly ACTION_TYPES: ActionsUnion<typeof ArticleActions.patchArticleStatusACs>;

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
}
