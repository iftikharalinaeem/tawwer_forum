/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import ReduxActions, { ActionsUnion } from "@library/state/ReduxActions";
import {
    IPostArticleResponseBody,
    IPostArticleRequestBody,
    IGetArticleRevisionResponseBody,
    IGetArticleRevisionRequestBody,
    IPostArticleRevisionResponseBody,
    IPostArticleRevisionRequestBody,
    IGetArticleResponseBody,
    IArticle,
} from "@knowledge/@types/api";
import { History } from "history";
import pathToRegexp from "path-to-regexp";
import * as route from "./route";
import ArticlePageActions from "@knowledge/modules/article/ArticlePageActions";
import LocationPickerActions from "../locationPicker/LocationPickerActions";
import { IStoreState } from "@knowledge/state/model";
import CategoryModel from "../categories/CategoryModel";

export default class EditorPageActions extends ReduxActions {
    // API actions
    public static readonly POST_ARTICLE_REQUEST = "@@articleEditor/POST_ARTICLE_REQUEST";
    public static readonly POST_ARTICLE_RESPONSE = "@@articleEditor/POST_ARTICLE_RESPONSE";
    public static readonly POST_ARTICLE_ERROR = "@@articleEditor/POST_ARTICLE_ERROR";

    public static readonly POST_REVISION_REQUEST = "@@articleEditor/POST_REVISION_REQUEST";
    public static readonly POST_REVISION_RESPONSE = "@@articleEditor/POST_REVISION_RESPONSE";
    public static readonly POST_REVISION_ERROR = "@@articleEditor/POST_REVISION_ERROR";

    public static readonly GET_ARTICLE_REQUEST = "@@articleEditor/GET_ARTICLE_REQUEST";
    public static readonly GET_ARTICLE_RESPONSE = "@@articleEditor/GET_ARTICLE_RESPONSE";
    public static readonly GET_ARTICLE_ERROR = "@@articleEditor/GET_ARTICLE_ERROR";

    public static readonly GET_REVISION_REQUEST = "@@articleEditor/GET_REVISION_REQUEST";
    public static readonly GET_REVISION_RESPONSE = "@@articleEditor/GET_REVISION_RESPONSE";
    public static readonly GET_REVISION_ERROR = "@@articleEditor/GET_REVISION_ERROR";

    // Frontend only actions
    public static readonly RESET = "@@articleEditor/RESET";

    /**
     * Union of all possible action types in this class.
     */
    public static ACTION_TYPES:
        | ActionsUnion<typeof EditorPageActions.postRevisionACs>
        | ActionsUnion<typeof EditorPageActions.postArticleACs>
        | ActionsUnion<typeof EditorPageActions.getRevisionACs>
        | ActionsUnion<typeof EditorPageActions.getArticleACs>
        | ReturnType<typeof EditorPageActions.createResetAction>;

    /**
     * Action creators for GET /articles/:id
     */
    private static getArticleACs = ReduxActions.generateApiActionCreators(
        EditorPageActions.GET_ARTICLE_REQUEST,
        EditorPageActions.GET_ARTICLE_RESPONSE,
        EditorPageActions.GET_ARTICLE_ERROR,
        // https://github.com/Microsoft/TypeScript/issues/10571#issuecomment-345402872
        {} as IPostArticleResponseBody,
        {} as any,
    );

    /**
     * Action creators for POST /articles
     */
    private static postArticleACs = ReduxActions.generateApiActionCreators(
        EditorPageActions.POST_ARTICLE_REQUEST,
        EditorPageActions.POST_ARTICLE_RESPONSE,
        EditorPageActions.POST_ARTICLE_ERROR,
        // https://github.com/Microsoft/TypeScript/issues/10571#issuecomment-345402872
        {} as IPostArticleResponseBody,
        {} as IPostArticleRequestBody,
    );

    /**
     * Action creators for GET /article-revisions/:id
     */
    private static getRevisionACs = ReduxActions.generateApiActionCreators(
        EditorPageActions.GET_REVISION_REQUEST,
        EditorPageActions.GET_REVISION_RESPONSE,
        EditorPageActions.GET_REVISION_ERROR,
        // https://github.com/Microsoft/TypeScript/issues/10571#issuecomment-345402872
        {} as IGetArticleRevisionResponseBody,
        {} as IGetArticleRevisionRequestBody,
    );

    /**
     * Action creators for POST /article-revisions
     */
    private static postRevisionACs = ReduxActions.generateApiActionCreators(
        EditorPageActions.POST_REVISION_REQUEST,
        EditorPageActions.POST_REVISION_RESPONSE,
        EditorPageActions.POST_REVISION_ERROR,
        // https://github.com/Microsoft/TypeScript/issues/10571#issuecomment-345402872
        {} as IPostArticleRevisionResponseBody,
        {} as IPostArticleRevisionRequestBody,
    );

    /**
     * Create a reset action
     */
    private static createResetAction() {
        return EditorPageActions.createAction(EditorPageActions.RESET);
    }

    /**
     * Reset the page state.
     */
    public reset = this.bindDispatch(EditorPageActions.createResetAction);

    /** Article page actions instance. */
    private articlePageActions: ArticlePageActions = new ArticlePageActions(this.dispatch, this.api);

    private locationPickerActions: LocationPickerActions = new LocationPickerActions(this.dispatch, this.api);

    /**
     * Initialize the editor page data based on our path.
     *
     * We have to scenarios:
     *
     * - /articles/add - Initialize a new article
     * - /articles/:id/editor - We already have a new article. Go fetch it.
     *
     * @param history - The history object.
     */
    public async initPageFromLocation(history: History) {
        const { location } = history;
        // Use the same path regex as our router.
        const addRegex = pathToRegexp(route.ADD_ROUTE);
        const editRegex = pathToRegexp(route.EDIT_ROUTE);

        // Check url
        if (addRegex.test(location.pathname)) {
            // We don't have an article so go create one.
            const article = await this.createArticle({
                knowledgeCategoryID: 1,
            });

            if (article) {
                const replacementUrl = route.makeEditUrl(article.data.articleID);
                const newLocation = {
                    ...location,
                    pathname: replacementUrl,
                };

                history.replace(newLocation);
            }
        } else if (editRegex.test(location.pathname)) {
            // We don't have an article, but we have ID for one. Go get it.
            const articleID = editRegex.exec(location.pathname)![1];
            const article = await this.getEditableArticleByID(articleID).then(
                response => (response ? response.data : undefined),
            );
            void this.fetchRevisionFromArticle(article);

            if (article) {
                this.dispatch((a, getState: () => IStoreState) => {
                    const category = CategoryModel.selectKbCategoryFragment(getState(), article!.articleID);
                    this.locationPickerActions.init(category);
                });
            }
        }
    }

    /**
     * Submit the editor's form data to the API.
     *
     * @param body - The body of the submit request.
     */
    public async submitNewRevision(body: IPostArticleRevisionRequestBody, history: History) {
        const result = await this.postRevision(body);
        // Our API request has failed
        if (!result) {
            return;
        }

        const { articleID } = result.data;
        const newArticle = await this.articlePageActions.getArticleByID(articleID);
        // Our API request failed.
        if (!newArticle) {
            return;
        }
        const { url } = newArticle.data;

        // Make the URL relative to the root of the site.
        const link = document.createElement("a");
        link.href = url;

        // Redirect to the new url.
        history.push(link.pathname);
    }

    /**
     * Post a revision to the API.
     *
     * @param data The revision data.
     */
    public postRevision(data: IPostArticleRevisionRequestBody) {
        return this.dispatchApi<IPostArticleRevisionResponseBody>(
            "post",
            "/article-revisions",
            EditorPageActions.postRevisionACs,
            data,
        );
    }

    /**
     * Fetch the current revision from an article resource through the API.
     */
    private async fetchRevisionFromArticle(article: IArticle | undefined) {
        if (article && article.articleRevisionID !== null) {
            await this.getRevisionByID(article.articleRevisionID);
        }
    }

    /**
     * Create a new article.
     *
     * @param data The article data.
     */
    private createArticle(data: IPostArticleRequestBody) {
        return this.dispatchApi<IPostArticleResponseBody>("post", `/articles`, EditorPageActions.postArticleACs, data);
    }

    /**
     * Get an existing revision by its ID.
     *
     * @param revisionID
     */
    private getRevisionByID(revisionID: number) {
        return this.dispatchApi<IGetArticleRevisionResponseBody>(
            "get",
            `/article-revisions/${revisionID}`,
            EditorPageActions.getRevisionACs,
            {},
        );
    }

    /**
     * Get an article for editing by its id.
     *
     * @param articleID
     */
    private getEditableArticleByID(articleID: string) {
        return this.dispatchApi<IGetArticleResponseBody>(
            "get",
            `/articles/${articleID}`,
            EditorPageActions.getArticleACs,
            {},
        );
    }
}
