/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IArticle, IArticleFragment } from "@knowledge/@types/api/article";
import { IRevision, IRevisionFragment } from "@knowledge/@types/api/articleRevision";
import { IKbCategory, IKbCategoryFragment } from "@knowledge/@types/api/kbCategory";
import { DefaultKbError } from "@knowledge/modules/common/KbErrorMessages";
import { IKbNavigationItem } from "@knowledge/navigation/state/NavigationModel";
import ModalLoader from "@library/modal/ModalLoader";
import RouteHandler from "@library/routing/RouteHandler";
import React from "react";
import { Route } from "react-router-dom";
import { makeEditorUrl } from "@knowledge/routes/makeEditorUrl";
import NavigationLoadingLayout from "@knowledge/navigation/NavigationLoadingLayout";
import { KbErrorPage } from "@knowledge/pages/KbErrorPage";

const editorPaths = ["/kb/articles/add", "/kb/articles/:id(\\d+)/editor"];

// Editor
const EDITOR_KEY = "EditorPageKey";
const loadEditor = () => import(/* webpackChunkName: "pages/kb/editor" */ "@knowledge/modules/editor/EditorPage");
export const EditorRoute = new RouteHandler(loadEditor, editorPaths, makeEditorUrl, ModalLoader, EDITOR_KEY);

/**
 * Get the route for editing a particular article ID.
 *
 * @param articleID - The articleID.
 */
export function makeRevisionsUrl(articleOrRevison: IArticleFragment | IArticle | IRevisionFragment | IRevision) {
    if ("articleRevisionID" in articleOrRevison) {
        return `/kb/articles/${articleOrRevison.articleID}/revisions/${articleOrRevison.articleRevisionID}`;
    } else {
        return `/kb/articles/${articleOrRevison.articleID}/revisions`;
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
    NavigationLoadingLayout,
);

const CATEGORIES_KEY = "CategoriesPageKey";
export const CategoryRoute = new RouteHandler(
    () => import(/* webpackChunkName: "pages/kb/categories" */ "@knowledge/modules/categories/CategoriesPage"),
    "/kb/categories/:id(\\d+)(-[^/]+)?",
    (category: IKbCategory | IKbCategoryFragment | IKbNavigationItem) => category.url,
    NavigationLoadingLayout,
    CATEGORIES_KEY,
);

export const CategoryPagedRoute = new RouteHandler(
    () => import(/* webpackChunkName: "pages/kb/categories" */ "@knowledge/modules/categories/CategoriesPage"),
    "/kb/categories/:id(\\d+)(-[^/]+)?/p:page(\\d+)",
    (category: IKbCategory | IKbCategoryFragment | IKbNavigationItem) => category.url,
    undefined,
    CATEGORIES_KEY,
);

export const ArticleListPageRoute = new RouteHandler(
    () => import(/* webpackChunkName: "pages/kb/articles" */ "@knowledge/modules/article/ArticleListPage"),
    "/kb/articles",
    (data?: undefined) => "/kb/articles",
);

export const SearchRoute = new RouteHandler(
    () => import(/* webpackChunkName: "pages/kb/search" */ "@knowledge/modules/search/SearchPage"),
    "/kb/search",
    (data?: undefined) => "/kb/search",
);

export const DraftsRoute = new RouteHandler(
    () => import(/* webpackChunkName: "pages/kb/drafts" */ "@knowledge/modules/drafts/DraftsPage"),
    "/kb/drafts",
    (data?: undefined) => "/kb/drafts",
    ModalLoader,
);

export const HomeRoute = new RouteHandler(
    () => import(/* webpackChunkName: "pages/kb/index" */ "@knowledge/pages/HomePage"),
    "/kb",
    (data?: undefined) => "/kb",
);

export const HomeAppRoute = new RouteHandler(
    () => import(/* webpackChunkName: "pages/kb/index" */ "@knowledge/pages/HomePage"),
    "/",
    (data?: undefined) => "/",
);

export const KnowledgeBaseRoute = new RouteHandler(
    () => import(/* webpackChunkName: "pages/kb/knowledge-base" */ "@knowledge/pages/KnowledgeBasePage"),
    "/kb/:urlCode([\\w\\d-]+)",
    (data: { urlCode: string }) => `/kb/${data.urlCode}`,
);

export const OrganizeCategoriesRoute = new RouteHandler(
    () => import(/* webpackChunkName: "pages/kb/organize-categories" */ "@knowledge/pages/OrganizeCategoriesPage"),
    "/kb/:id/organize-categories",
    (data: { kbID: number }) => `/kb/${data.kbID}/organize-categories`,
    ModalLoader,
);

const NotFound = () => {
    return <KbErrorPage defaultError={DefaultKbError.NOT_FOUND} />;
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
        EditorRoute.route,
        RevisionsRoute.route,
        ArticleRoute.route,
        CategoryRoute.route,
        CategoryPagedRoute.route,
        SearchRoute.route,
        DraftsRoute.route,
        OrganizeCategoriesRoute.route,
        ArticleListPageRoute.route,
        KnowledgeBaseRoute.route,
        HomeRoute.route,
        HomeAppRoute.route,
        <Route component={NotFound} key={"not found"} />,
    ];
}
