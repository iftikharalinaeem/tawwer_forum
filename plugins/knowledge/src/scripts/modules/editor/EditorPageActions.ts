/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import ReduxActions, { ActionsUnion } from "@library/state/ReduxActions";
import {
    IPostArticleResponseBody,
    IPostArticleRequestBody,
    IGetArticleResponseBody,
    IPatchArticleRequestBody,
    IPatchArticleResponseBody,
    IGetArticleRevisionsResponseBody,
    IGetArticleRevisionsRequestBody,
} from "@knowledge/@types/api";
import { History } from "history";
import pathToRegexp from "path-to-regexp";
import * as route from "./route";
import ArticlePageActions from "@knowledge/modules/article/ArticlePageActions";
import LocationPickerActions from "@knowledge/modules/locationPicker/LocationPickerActions";
import qs from "qs";
import ArticleActions from "../article/ArticleActions";

export default class EditorPageActions extends ReduxActions {
    // API actions
    public static readonly POST_ARTICLE_REQUEST = "@@articleEditor/POST_ARTICLE_REQUEST";
    public static readonly POST_ARTICLE_RESPONSE = "@@articleEditor/POST_ARTICLE_RESPONSE";
    public static readonly POST_ARTICLE_ERROR = "@@articleEditor/POST_ARTICLE_ERROR";

    // API actions
    public static readonly PATCH_ARTICLE_REQUEST = "@@articleEditor/PATCH_ARTICLE_REQUEST";
    public static readonly PATCH_ARTICLE_RESPONSE = "@@articleEditor/PATCH_ARTICLE_RESPONSE";
    public static readonly PATCH_ARTICLE_ERROR = "@@articleEditor/PATCH_ARTICLE_ERROR";

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
        | ActionsUnion<typeof EditorPageActions.postArticleACs>
        | ActionsUnion<typeof EditorPageActions.getRevisionACs>
        | ActionsUnion<typeof EditorPageActions.getArticleACs>
        | ActionsUnion<typeof EditorPageActions.patchArticleACs>
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
     * Action creators for POST /articles
     */
    private static patchArticleACs = ReduxActions.generateApiActionCreators(
        EditorPageActions.PATCH_ARTICLE_REQUEST,
        EditorPageActions.PATCH_ARTICLE_RESPONSE,
        EditorPageActions.PATCH_ARTICLE_ERROR,
        // https://github.com/Microsoft/TypeScript/issues/10571#issuecomment-345402872
        {} as IPatchArticleRequestBody,
        {} as IPatchArticleResponseBody,
    );

    /**
     * Action creators for GET /article-revisions/:id
     */
    private static getRevisionACs = ReduxActions.generateApiActionCreators(
        EditorPageActions.GET_REVISION_REQUEST,
        EditorPageActions.GET_REVISION_RESPONSE,
        EditorPageActions.GET_REVISION_ERROR,
        // https://github.com/Microsoft/TypeScript/issues/10571#issuecomment-345402872
        {} as IGetArticleRevisionsResponseBody,
        {} as IGetArticleRevisionsRequestBody,
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
    private articleActions: ArticleActions = new ArticleActions(this.dispatch, this.api);

    /** Location picker page actions instance. */
    private locationPickerActions: LocationPickerActions = new LocationPickerActions(this.dispatch, this.api);

    /**
     * Create an article and redirect to the edit page for it.
     *
     * @param history - The history object for redirecting.
     */
    public async createArticleForEdit(history: History) {
        const queryParams = qs.parse(history.location.search.replace(/^\?/, ""));
        const initialCategoryID = "knowledgeCategoryID" in queryParams ? queryParams.knowledgeCategoryID : null;

        // We don't have an article so go create one.
        const response = await this.postArticle({
            knowledgeCategoryID: initialCategoryID,
        });

        if (response) {
            const article = response.data;

            // Initialize the category picker if we have a category ID.
            if (initialCategoryID !== null) {
                this.locationPickerActions.initLocationPickerFromArticle(article);
            }

            // Redirect
            const replacementUrl = route.makeEditUrl(article);
            const newLocation = {
                ...history.location,
                pathname: replacementUrl,
                search: "",
            };

            history.replace(newLocation);
        }
    }

    /**
     * Fetch an existing article for editing.
     *
     * @param articleID - The ID of the article to fetch.
     */
    public async fetchArticleForEdit(articleID: number) {
        // We don't have an article, but we have ID for one. Go get it.
        const response = await this.getEditableArticleByID(articleID);

        if (response && response.data) {
            await this.locationPickerActions.initLocationPickerFromArticle(response.data);
        }
    }

    /**
     * Submit the editor's form data to the API.
     *
     * @param body - The body of the submit request.
     */
    public async updateArticle(article: IPatchArticleRequestBody, history: History) {
        const articleResult = await this.patchArticle(article);
        // Our API request has failed
        if (!articleResult) {
            return;
        }

        const { articleID } = article;
        const newArticle = await this.articleActions.fetchByID({ articleID });
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
     * Create a new article.
     *
     * @param data The article data.
     */
    private postArticle(data: IPostArticleRequestBody) {
        return this.dispatchApi<IPostArticleResponseBody>("post", `/articles`, EditorPageActions.postArticleACs, data);
    }

    /**
     * Update an article
     */
    private patchArticle(data: IPatchArticleRequestBody) {
        const { articleID, ...rest } = data;
        return this.dispatchApi<IPatchArticleResponseBody>(
            "patch",
            `/articles/${articleID}`,
            EditorPageActions.patchArticleACs,
            rest,
        );
    }

    /**
     * Get an existing revision by its ID.
     *
     * @param revisionID
     */
    private getRevisionByID(revisionID: number) {
        return this.dispatchApi<IGetArticleRevisionsResponseBody>(
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
    private getEditableArticleByID(articleID: number) {
        return this.dispatchApi<IGetArticleResponseBody>(
            "get",
            `/articles/${articleID}/edit`,
            EditorPageActions.getArticleACs,
            {},
        );
    }
}
