/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { Route } from "react-router-dom";
import ErrorPage from "@knowledge/routes/ErrorPage";
import { LoadStatus } from "@library/@types/api";
import RouteHandler from "@knowledge/routes/RouteHandler";
import { ModalLoader } from "@library/components/modal";
import {
    IArticleFragment,
    IArticle,
    IRevisionFragment,
    IRevision,
    IResponseArticleDraft,
    IKbCategoryFragment,
    IKbCategory,
    IKbCategoryMultiTypeFragment,
} from "@knowledge/@types/api";
import { formatUrl } from "@library/application";

/**
 * Get the route for editing a particular article ID.
 *
 * @param articleID - The articleID.
 */
function makeEditorUrl(
    data?: IArticleFragment | IArticle | IRevisionFragment | IRevision | IResponseArticleDraft | undefined,
) {
    if (data === undefined) {
        return formatUrl("/kb/articles/add");
    } else if ("articleRevisionID" in data) {
        return formatUrl(`/kb/articles/${data.articleID}/editor?revisionID=${data.articleRevisionID}`);
    } else if ("draftID" in data) {
        if (data.recordType === "article" && data.recordID !== null) {
            return formatUrl(`/kb/articles/${data.recordID}/editor?draftID=${data.draftID}`);
        } else {
            return formatUrl(`/kb/articles/add?draftID=${data.draftID}`);
        }
    } else {
        return formatUrl(`/kb/articles/${data.articleID}/editor`);
    }
}

// Editor
const EDITOR_KEY = "EditorPageKey";
const loadEditor = () => import(/* webpackChunkName: "pages/kb/editor" */ "@knowledge/modules/editor/EditorPage");
const EditorAddRoute = new RouteHandler(loadEditor, "/kb/articles/add", makeEditorUrl, ModalLoader, EDITOR_KEY);
export const EditorRoute = new RouteHandler(
    loadEditor,
    "/kb/articles/:id(\\d+)/editor",
    makeEditorUrl,
    ModalLoader,
    EDITOR_KEY,
);

/**
 * Get the route for editing a particular article ID.
 *
 * @param articleID - The articleID.
 */
export function makeRevisionsUrl(articleOrRevison: IArticleFragment | IArticle | IRevisionFragment | IRevision) {
    if ("articleRevisionID" in articleOrRevison) {
        return formatUrl(`/kb/articles/${articleOrRevison.articleID}/revisions/${articleOrRevison.articleRevisionID}`);
    } else {
        return formatUrl(`/kb/articles/${articleOrRevison.articleID}/revisions`);
    }
}
export const RevisionsRoute = new RouteHandler(
    () => import(/* webpackChunkName: "pages/kb/revisions" */ "@knowledge/modules/editor/RevisionsPage"),
    "/kb/articles/:id(\\d+)/revisions/:revisionID(\\d+)?",
    makeRevisionsUrl,
    ModalLoader,
);

export const ArticleRoute = new RouteHandler(
    () => import(/* webpackChunkName: "pages/kb/article" */ "@knowledge/modules/article/ArticlePage"),
    "/kb/articles/:id(\\d+)(-[^/]+)?",
    (article: IArticle | IArticleFragment) => article.url,
);

export const HomeRoute = new RouteHandler(
    () => import(/* webpackChunkName: "pages/kb/index" */ "@knowledge/modules/home/HomePage"),
    "/kb",
    () => formatUrl("/kb"),
);

export const CategoryRoute = new RouteHandler(
    () => import(/* webpackChunkName: "pages/kb/categories" */ "@knowledge/modules/categories/CategoriesPage"),
    "/kb/categories/:id(\\d+)(-[^/]+)?",
    (category: IKbCategory | IKbCategoryFragment | IKbCategoryMultiTypeFragment) => category.url,
);

export const SearchRoute = new RouteHandler(
    () => import(/* webpackChunkName: "pages/kb/search" */ "@knowledge/modules/search/SearchPage"),
    "/kb/search",
    (data?: undefined) => formatUrl("/kb/search"),
);

export const DraftsRoute = new RouteHandler(
    () => import(/* webpackChunkName: "pages/kb/drafts" */ "@knowledge/modules/drafts/DraftsPage"),
    "/kb/drafts",
    (data?: undefined) => formatUrl("/kb/drafts"),
    ModalLoader,
);

const NotFound = () => {
    return <ErrorPage loadable={{ status: LoadStatus.ERROR, error: { status: 404, message: "Page not found." } }} />;
};

/**
 * Get the data for routes that can render in a modal.
 *
 * If a data can also be shown in the Modal container place it in {@link getModalRouteData}
 *
 * We can't return actual react components here because the React Router <Switch>
 * only looks at its direct children. Trying to join separate components of routes using
 * <React.Fragment> does not currently work.
 */
export function getPageRoutes() {
    return [
        EditorAddRoute.route,
        EditorRoute.route,
        RevisionsRoute.route,
        ArticleRoute.route,
        HomeRoute.route,
        CategoryRoute.route,
        SearchRoute.route,
        DraftsRoute.route,
        <Route component={NotFound} key={"not found"} />,
    ];
}
