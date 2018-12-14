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
    IGetArticleDraftsRequest,
    IGetArticleDraftsResponse,
    IGetArticleDraftResponse,
    IGetArticleDraftRequest,
    IPostArticleDraftResponse,
    IPostArticleDraftRequest,
    IPatchArticleDraftResponse,
    IPatchArticleDraftRequest,
    IDeleteArticleDraftResponse,
    IDeleteArticleDraftRequest,
    IPatchArticleRequestBody,
    IPatchArticleResponseBody,
} from "@knowledge/@types/api";
import apiv2 from "@library/apiv2";
import ArticleModel from "./ArticleModel";
import { IApiResponse } from "@library/@types/api";

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

    public static readonly GET_DRAFTS_REQUEST = "@@article/GET_DRAFTS_REQUEST";
    public static readonly GET_DRAFTS_RESPONSE = "@@article/GET_DRAFTS_RESPONSE";
    public static readonly GET_DRAFTS_ERROR = "@@article/GET_DRAFTS_ERROR";

    public static readonly POST_DRAFT_REQUEST = "@@article/POST_DRAFT_REQUEST";
    public static readonly POST_DRAFT_RESPONSE = "@@article/POST_DRAFT_RESPONSE";
    public static readonly POST_DRAFT_ERROR = "@@article/POST_DRAFT_ERROR";

    public static readonly PATCH_DRAFT_REQUEST = "@@article/PATCH_DRAFT_REQUEST";
    public static readonly PATCH_DRAFT_RESPONSE = "@@article/PATCH_DRAFT_RESPONSE";
    public static readonly PATCH_DRAFT_ERROR = "@@article/PATCH_DRAFT_ERROR";

    public static readonly GET_DRAFT_REQUEST = "@@article/GET_DRAFT_REQUEST";
    public static readonly GET_DRAFT_RESPONSE = "@@article/GET_DRAFT_RESPONSE";
    public static readonly GET_DRAFT_ERROR = "@@article/GET_DRAFT_ERROR";

    public static readonly DELETE_DRAFT_REQUEST = "@@article/DELETE_DRAFT_REQUEST";
    public static readonly DELETE_DRAFT_RESPONSE = "@@article/DELETE_DRAFT_RESPONSE";
    public static readonly DELETE_DRAFT_ERROR = "@@article/DELETE_DRAFT_ERROR";

    /**
     * Union of all possible action types in this class.
     */
    public static readonly ACTION_TYPES:
        | ActionsUnion<typeof ArticleActions.patchArticleStatusACs>
        | ActionsUnion<typeof ArticleActions.patchArticleACs>
        | ActionsUnion<typeof ArticleActions.getArticleACs>
        | ActionsUnion<typeof ArticleActions.getDraftACs>
        | ActionsUnion<typeof ArticleActions.getDraftsACs>
        | ActionsUnion<typeof ArticleActions.postDraftACs>
        | ActionsUnion<typeof ArticleActions.patchDraftACs>
        | ActionsUnion<typeof ArticleActions.deleteDraftACs>
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

    /**
     * Static action creators for the /articles/drafts endpoint.
     */
    private static readonly getDraftsACs = ReduxActions.generateApiActionCreators(
        ArticleActions.GET_DRAFTS_REQUEST,
        ArticleActions.GET_DRAFTS_RESPONSE,
        ArticleActions.GET_DRAFTS_ERROR,
        {} as IGetArticleDraftsResponse,
        {} as IGetArticleDraftsRequest & { identifier: string },
    );

    public getDrafts(request: IGetArticleDraftsRequest, identifier: string) {
        return this.dispatchApi<IGetArticleDraftsResponse>(
            "get",
            `/articles/drafts`,
            ArticleActions.getDraftsACs,
            request,
            {
                identifier,
            },
        );
    }

    /**
     * Static action creators for the /articles/drafts endpoint.
     */
    private static readonly getDraftACs = ReduxActions.generateApiActionCreators(
        ArticleActions.GET_DRAFT_REQUEST,
        ArticleActions.GET_DRAFT_RESPONSE,
        ArticleActions.GET_DRAFT_ERROR,
        {} as IGetArticleDraftResponse,
        {} as IGetArticleDraftRequest,
    );

    public getDraft(request: IGetArticleDraftRequest) {
        const { draftID } = request;
        return this.dispatchApi<IGetArticleDraftResponse>(
            "get",
            `/articles/drafts/${draftID}`,
            ArticleActions.getDraftACs,
            request,
        );
    }

    // POST /articles/drafts
    public static readonly postDraftACs = ReduxActions.generateApiActionCreators(
        ArticleActions.POST_DRAFT_REQUEST,
        ArticleActions.POST_DRAFT_RESPONSE,
        ArticleActions.POST_DRAFT_ERROR,
        {} as IPostArticleDraftResponse,
        {} as IPostArticleDraftRequest & { tempID?: string },
    );

    public postDraft(request: IPostArticleDraftRequest, tempID: string) {
        return this.dispatchApi<IPostArticleDraftResponse>(
            "post",
            "/articles/drafts",
            ArticleActions.postDraftACs,
            request,
            {
                tempID,
            },
        );
    }

    /**
     * Static action creators for the /articles/drafts endpoint.
     */
    private static readonly patchDraftACs = ReduxActions.generateApiActionCreators(
        ArticleActions.PATCH_DRAFT_REQUEST,
        ArticleActions.PATCH_DRAFT_RESPONSE,
        ArticleActions.PATCH_DRAFT_ERROR,
        {} as IPatchArticleDraftResponse,
        {} as IPatchArticleDraftRequest,
    );

    public patchDraft(request: IPatchArticleDraftRequest) {
        const { draftID } = request;
        return this.dispatchApi<IPatchArticleDraftResponse>(
            "patch",
            `/articles/drafts/${draftID}`,
            ArticleActions.patchDraftACs,
            request,
        );
    }

    /**
     * Static action creators for the /articles/drafts endpoint.
     */
    private static readonly deleteDraftACs = ReduxActions.generateApiActionCreators(
        ArticleActions.DELETE_DRAFT_REQUEST,
        ArticleActions.DELETE_DRAFT_RESPONSE,
        ArticleActions.DELETE_DRAFT_ERROR,
        {} as IDeleteArticleDraftResponse,
        {} as IDeleteArticleDraftRequest,
    );

    public deleteDraft(request: IDeleteArticleDraftRequest) {
        return this.dispatchApi<IDeleteArticleDraftResponse>(
            "delete",
            `/articles/drafts/${request.draftID}`,
            ArticleActions.deleteDraftACs,
            request,
        );
    }

    public static mapDispatchToProps(dispatch): IArticleActionsProps {
        return {
            articleActions: new ArticleActions(dispatch, apiv2),
        };
    }

    /// PATCH /articles/:id

    public static readonly PATCH_ARTICLE_REQUEST = "@@article/PATCH_ARTICLE_REQUEST";
    public static readonly PATCH_ARTICLE_RESPONSE = "@@article/PATCH_ARTICLE_RESPONSE";
    public static readonly PATCH_ARTICLE_ERROR = "@@article/PATCH_ARTICLE_ERROR";
    private static patchArticleACs = ReduxActions.generateApiActionCreators(
        ArticleActions.PATCH_ARTICLE_REQUEST,
        ArticleActions.PATCH_ARTICLE_RESPONSE,
        ArticleActions.PATCH_ARTICLE_ERROR,
        // https://github.com/Microsoft/TypeScript/issues/10571#issuecomment-345402872
        {} as IPatchArticleRequestBody,
        {} as IPatchArticleResponseBody,
    );

    public patchArticle(data: IPatchArticleRequestBody) {
        const { articleID, ...rest } = data;
        return this.dispatchApi<IPatchArticleResponseBody>(
            "patch",
            `/articles/${articleID}`,
            ArticleActions.patchArticleACs,
            rest,
            { articleID },
        );
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
        const { articleID, ...rest } = options;
        return this.dispatchApi<IGetArticleResponseBody>(
            "get",
            `/articles/${options.articleID}?expand=all`,
            ArticleActions.getArticleACs,
            rest,
            { articleID },
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
        const { revisionID, ...rest } = options;
        const existingRevision = ArticleModel.selectRevision(this.getState(), revisionID);
        if (existingRevision) {
            const revResponse: IApiResponse<IGetRevisionResponseBody> = { data: existingRevision, status: 200 };
            this.dispatch(ArticleActions.getRevisionACs.response(revResponse, options));
            return Promise.resolve(revResponse);
        } else {
            return this.dispatchApi<IGetRevisionResponseBody>(
                "get",
                `/article-revisions/${options.revisionID}`,
                ArticleActions.getRevisionACs,
                rest,
                { revisionID },
            );
        }
    };
}
