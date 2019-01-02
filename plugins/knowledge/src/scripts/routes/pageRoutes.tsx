/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { Route, Redirect } from "react-router-dom";
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
} from "@knowledge/@types/api";
import { formatUrl } from "@library/application";
import { IKbNavigationItem } from "@knowledge/@types/api";

/**
 * Get the route for editing a particular article ID.
 *
 * @param articleID - The articleID.
 */
function makeEditorUrl(
    data?:
        | IArticleFragment
        | IArticle
        | IRevisionFragment
        | IRevision
        | IResponseArticleDraft
        | IKbCategoryFragment
        | undefined,
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
    } else if ("knowledgeCategoryID" in data && "parentID" in data) {
        return formatUrl(`/kb/articles/add?knowledgeCategoryID=${data.knowledgeCategoryID}`);
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

export const DebugRoute = new RouteHandler(
    () => import(/* webpackChunkName: "pages/kb/debug" */ "@knowledge/DebugPage"),
    "/kb/debug",
    () => formatUrl("/kb/debug"),
);

export const CategoryRoute = new RouteHandler(
    () => import(/* webpackChunkName: "pages/kb/categories" */ "@knowledge/modules/categories/CategoriesPage"),
    "/kb/categories/:id(\\d+)(-[^/]+)?",
    (category: IKbCategory | IKbCategoryFragment | IKbNavigationItem) => category.url,
);

export const SearchRoute = new RouteHandler(
    () => import(/* webpackChunkName: "pages/kb/search" */ "@knowledge/modules/search/SearchPage"),
    "/kb/search",
    (data?: undefined) => formatUrl("/kb/search"),
);
const SearchRedirect = () => <Redirect to={SearchRoute.url(undefined)} />;

export const DraftsRoute = new RouteHandler(
    () => import(/* webpackChunkName: "pages/kb/drafts" */ "@knowledge/modules/drafts/DraftsPage"),
    "/kb/drafts",
    (data?: undefined) => formatUrl("/kb/drafts"),
    ModalLoader,
);

export const OrganizeCategoriesRoute = new RouteHandler(
    () => import(/* webpackChunkName: "pages/kb/organize-categories" */ "@knowledge/pages/OrganizeCategoriesPage"),
    "/kb/:id/organize-categories",
    (data: { kbID: number }) => formatUrl(`/kb/${data.kbID}/organize-categories`),
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
        DebugRoute.route,
        CategoryRoute.route,
        SearchRoute.route,
        DraftsRoute.route,
        OrganizeCategoriesRoute.route,
        <Route exact path="/kb" component={SearchRedirect} key={"search redirect"} />,
        <Route component={NotFound} key={"not found"} />,
    ];
}
