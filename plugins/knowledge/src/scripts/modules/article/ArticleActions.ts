/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import {
    IArticle,
    IArticleFragment,
    IDeleteArticleDraftRequest,
    IDeleteArticleDraftResponse,
    IGetArticleDraftRequest,
    IGetArticleDraftResponse,
    IGetArticleDraftsRequest,
    IGetArticleDraftsResponse,
    IGetArticleFromDiscussionRequest,
    IGetArticleFromDiscussionResponse,
    IGetArticleRequestBody,
    IGetArticleResponseBody,
    IGetArticleLocalesRequestBody,
    IGetArticleLocalesResponseBody,
    IPatchArticleDraftRequest,
    IPatchArticleDraftResponse,
    IPatchArticleRequestBody,
    IPatchArticleResponseBody,
    IPatchArticleStatusRequestBody,
    IPatchArticleStatusResponseBody,
    IPostArticleDraftRequest,
    IPostArticleDraftResponse,
    IPostArticleRequestBody,
    IPostArticleResponseBody,
    IRelatedArticle,
    IFeatureArticle,
} from "@knowledge/@types/api/article";
import {
    IGetArticleRevisionsRequestBody,
    IGetArticleRevisionsResponseBody,
    IGetRevisionRequestBody,
    IGetRevisionResponseBody,
} from "@knowledge/@types/api/articleRevision";
import ArticleModel from "@knowledge/modules/article/ArticleModel";
import { IKnowledgeAppStoreState } from "@knowledge/state/model";
import { IApiError, IApiResponse, LoadStatus } from "@library/@types/api/core";
import apiv2 from "@library/apiv2";
import ReduxActions, { ActionsUnion, bindThunkAction } from "@library/redux/ReduxActions";
import actionCreatorFactory from "typescript-fsa";
import NavigationActions from "@knowledge/navigation/state/NavigationActions";
import { getCurrentLocale } from "@vanilla/i18n";
import { all } from "bluebird";
import { useDispatch } from "react-redux";
import { useMemo } from "react";

export interface IArticleActionsProps {
    articleActions: ArticleActions;
}

const createAction = actionCreatorFactory("@@article");

interface IHelpfulParams {
    articleID: number;
    helpful: "yes" | "no";
}

export interface IRelatedArticles {
    articleID: number;
    locale: string;
    limit?: number;
    minimumArticles?: number;
}

/**
 * Actions for the article page.
 */
export default class ArticleActions extends ReduxActions<IKnowledgeAppStoreState> {
    private static readonly DEFAULT_ARTICLES_LIMIT = 10;

    public static putReactACs = createAction.async<IHelpfulParams, IArticle, IApiError>("PUT_REACT");

    public reactHelpful = (params: IHelpfulParams) => {
        const { articleID, ...body } = params;
        const apiThunk = bindThunkAction(ArticleActions.putReactACs, async () => {
            const response = await this.api.put(`/articles/${articleID}/react`, body);
            return response.data;
        })(params);
        return this.dispatch(apiThunk);
    };

    public static getArticleACs = createAction.async<IGetArticleRequestBody, IGetArticleResponseBody, IApiError>(
        "GET_ARTICLE",
    );

    public static getArticleLocalesACs = createAction.async<
        IGetArticleLocalesRequestBody,
        IGetArticleLocalesResponseBody,
        IApiError
    >("GET_LOCALES");

    // FSA actions.

    // Old style actions

    public static readonly GET_ARTICLE_REQUEST = "@@article/GET_ARTICLE_REQUEST";
    public static readonly GET_ARTICLE_RESPONSE = "@@article/GET_ARTICLE_RESPONSE";
    public static readonly GET_ARTICLE_ERROR = "@@article/GET_ARTICLE_ERROR";

    public static readonly GET_ARTICLE_LOCALES_REQUEST = "@@article/GET_ARTICLE_LOCALES_REQUEST";
    public static readonly GET_ARTICLE_LOCALES_RESPONSE = "@@article/GET_ARTICLE_LOCALES_RESPONSE";
    public static readonly GET_ARTICLE_LOCALES_ERROR = "@@article/GET_ARTICLE_LOCALES_ERROR";

    public static readonly GET_ARTICLES_REQUEST = "@@article/GET_ARTICLES_REQUEST";
    public static readonly GET_ARTICLES_RESPONSE = "@@article/GET_ARTICLES_RESPONSE";
    public static readonly GET_ARTICLES_ERROR = "@@article/GET_ARTICLES_ERROR";

    // Raw actions for getting a knowledge category
    private static getArticlesACs = ReduxActions.generateApiActionCreators(
        ArticleActions.GET_ARTICLES_REQUEST,
        ArticleActions.GET_ARTICLES_RESPONSE,
        ArticleActions.GET_ARTICLES_ERROR,
        // https://github.com/Microsoft/TypeScript/issues/10571#issuecomment-345402872
        [] as IArticleFragment[],
        {},
    );

    public getArticles = (
        knowledgeCategoryID: number,
        page: number = 1,
        limit: number = ArticleActions.DEFAULT_ARTICLES_LIMIT,
    ) => {
        const query = {
            knowledgeCategoryID,
            expand: "excerpt",
            locale: getCurrentLocale(),
            limit,
            page,
        };
        return this.dispatchApi("get", `/articles`, ArticleActions.getArticlesACs, query);
    };

    public static getRelatedArticleACs = createAction.async<IGetArticleRequestBody, IRelatedArticle[], IApiError>(
        "GET_RELATED_ARTICLES",
    );

    public getRelatedArticles = (query: IRelatedArticles) => {
        const { articleID, ...params } = query;

        const existingLoadable = this.getState().knowledge.articles.relatedArticlesLoadable[articleID];
        if (existingLoadable && existingLoadable.status !== LoadStatus.PENDING) {
            return existingLoadable;
        }

        const apiThunk = bindThunkAction(ArticleActions.getRelatedArticleACs, async () => {
            const response = await this.api.get(`/articles/${articleID}/articlesRelated`, { params });
            return response.data;
        })(query);
        return this.dispatch(apiThunk);
    };
    public static putFeaturedArticles = createAction.async<IFeatureArticle, IGetArticleResponseBody, IApiError>(
        "PUT_FEATURED_ARTICLES",
    );

    public putFeaturedArticles = (params: IFeatureArticle) => {
        const { articleID, ...body } = params;
        const apiThunk = bindThunkAction(ArticleActions.putFeaturedArticles, async () => {
            const response = await this.api.put(`/articles/${articleID}/featured`, body);
            return response.data;
        })(params);
        return this.dispatch(apiThunk);
    };

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
        | ActionsUnion<typeof ArticleActions.getArticlesACs>
        | ActionsUnion<typeof ArticleActions.postArticleACs>
        | ActionsUnion<typeof ArticleActions.patchArticleStatusACs>
        | ActionsUnion<typeof ArticleActions.patchArticleACs>
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

    private navigationActions = new NavigationActions(this.dispatch, this.api, this.getState);

    public getDrafts(request: IGetArticleDraftsRequest, identifier: string) {
        return this.dispatchApi<IGetArticleDraftsResponse>(
            "get",
            `/articles/drafts`,
            ArticleActions.getDraftsACs,
            { ...request, expand: "all" },
            {
                identifier,
            },
        );
    }

    public static getFromDiscussionACs = createAction.async<
        IGetArticleFromDiscussionRequest,
        IGetArticleFromDiscussionResponse,
        IApiError
    >("GET_FROM_DISCUSSION");

    public getFromDiscussion(request: IGetArticleFromDiscussionRequest) {
        const apiThunk = bindThunkAction(ArticleActions.getFromDiscussionACs, async () => {
            const params = { ...request };
            const response = await this.api.get("/articles/from-discussion", {
                params,
            });
            return response.data;
        })(request);
        return this.dispatch(apiThunk);
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

    // POST /articles
    public static readonly POST_ARTICLE_REQUEST = "@@article/POST_ARTICLE_REQUEST";
    public static readonly POST_ARTICLE_RESPONSE = "@@article/POST_ARTICLE_RESPONSE";
    public static readonly POST_ARTICLE_ERROR = "@@article/POST_ARTICLE_ERROR";

    /**
     * Action creators for POST /articles
     */
    private static postArticleACs = ReduxActions.generateApiActionCreators(
        ArticleActions.POST_ARTICLE_REQUEST,
        ArticleActions.POST_ARTICLE_RESPONSE,
        ArticleActions.POST_ARTICLE_ERROR,
        // https://github.com/Microsoft/TypeScript/issues/10571#issuecomment-345402872
        {} as IPostArticleResponseBody,
        {} as IPostArticleRequestBody,
    );

    /**
     * Create a new article.
     *
     * @param data The article data.
     */
    public async postArticle(data: IPostArticleRequestBody) {
        const articleResponse = await this.dispatchApi<IPostArticleResponseBody>(
            "post",
            `/articles`,
            ArticleActions.postArticleACs,
            data,
        );

        if (articleResponse && articleResponse.data) {
            await this.navigationActions.getNavigationFlat(articleResponse.data.knowledgeBaseID, true);
        }
        return articleResponse;
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
        {} as IPatchArticleResponseBody,
        {} as IPatchArticleRequestBody,
    );

    public patchArticle = async (data: IPatchArticleRequestBody) => {
        const { articleID, ...rest } = data;
        const articleResponse = await this.dispatchApi<IPatchArticleResponseBody>(
            "patch",
            `/articles/${articleID}`,
            ArticleActions.patchArticleACs,
            rest,
            { articleID },
        );

        if (articleResponse && articleResponse.data) {
            await this.navigationActions.getNavigationFlat(articleResponse.data.knowledgeBaseID, true);
        }
        return articleResponse;
    };

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
    public fetchByID = async (
        options: IGetArticleRequestBody,
        force: boolean = false,
    ): Promise<IGetArticleResponseBody | undefined> => {
        const { articleID, ...rest } = options;

        const existingArticle = ArticleModel.selectArticle(this.getState(), articleID);
        const params = { ...rest, expand: "all" };

        if (!params.locale) {
            params.locale = getCurrentLocale();
        }

        if (existingArticle && !force) {
            const articleResponse: IApiResponse<IGetArticleResponseBody> = { data: existingArticle, status: 200 };
            this.dispatch(ArticleActions.getArticleACs.done({ params: options, result: existingArticle }, options));
            return Promise.resolve(articleResponse.data);
        }

        const thunk = bindThunkAction(ArticleActions.getArticleACs, async () => {
            const response = await this.api.get(`/articles/${options.articleID}`, { params });
            return response.data;
        })(options);

        return this.dispatch(thunk);
    };

    /**
     * Get an article Locales by its ID from the API.
     */
    public fetchLocales = (options: IGetArticleLocalesRequestBody, force: boolean = false) => {
        const { articleID } = options;

        const existingLocale = ArticleModel.selectArticleLocale(this.getState(), articleID);

        if (!force && existingLocale.status !== LoadStatus.PENDING) {
            return existingLocale;
        }

        const apiThunk = bindThunkAction(ArticleActions.getArticleLocalesACs, async () => {
            const response = await this.api.get(`/articles/${articleID}/translations`);
            return response.data;
        })(options);
        return this.dispatch(apiThunk);
    };

    public fetchRevisionsForArticle = (options: IGetArticleRevisionsRequestBody) => {
        const locale = getCurrentLocale();
        return this.dispatchApi<IGetArticleRevisionsResponseBody>(
            "get",
            `/articles/${options.articleID}/revisions`,
            ArticleActions.getArticleRevisionsACs,
            {
                locale,
                ...options,
            },
        );
    };

    public fetchRevisionByID = (options: IGetRevisionRequestBody) => {
        const { revisionID, ...rest } = options;
        const existingRevision = ArticleModel.selectRevision(this.getState(), revisionID);
        if (existingRevision) {
            const revResponse: IApiResponse<IGetRevisionResponseBody> = {
                data: existingRevision,
                status: 200,
            };
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

export function useArticleActions() {
    const dispatch = useDispatch();
    const actions = useMemo(() => new ArticleActions(dispatch, apiv2), [dispatch]);
    return actions;
}
