/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { Route } from "react-router-dom";
import Loadable from "react-loadable";
import FullPageLoader from "@library/components/FullPageLoader";
import { ADD_ROUTE, EDIT_ROUTE } from "@knowledge/modules/editor/route";

export const ARTICLE_ROUTE = "/kb/articles/:id(\\d+)(-[^/]+)?";
export const ARTICLE_REVISIONS_ROUTE = "/kb/articles/:id(\\d+)(-[^/]+/revisions)?";
export const CATEGORIES_ROUTE = "/kb/categories/:id(\\d+)(-[^/]+)?";

/** A loadable version of the Editor Page */
const EditorPage = Loadable({
    loading: FullPageLoader,
    loader: () => import(/* webpackChunkName: "pages/kb/editor" */ "@knowledge/modules/editor/EditorPage"),
});

/** A loadable version of the article page. */
const ArticlePage = Loadable({
    loading: FullPageLoader,
    loader: () => import(/* webpackChunkName: "pages/kb/article" */ "@knowledge/modules/article/ArticlePage"),
});

/** A loadable version of the article page. */
const ArticleRevisionsPage = Loadable({
    loading: FullPageLoader,
    loader: () => import(/* webpackChunkName: "pages/kb/article" */ "@knowledge/modules/article/ArticleRevisionsPage"),
});

/** A loadable version of the HomePage component. */
const HomePage = Loadable({
    loading: FullPageLoader,
    loader: () => import(/* webpackChunkName: "pages/kb/index" */ "@knowledge/modules/home/HomePage"),
});

/** A loadable version of the article page. */
const CategoriesPage = Loadable({
    loading: FullPageLoader,
    loader: () => import(/* webpackChunkName: "pages/kb/categories" */ "@knowledge/modules/categories/CategoriesPage"),
});

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
        <Route exact path="/kb" component={HomePage} key={"/kb"} />,
        <Route exact path={ARTICLE_REVISIONS_ROUTE} component={ArticleRevisionsPage} key={ARTICLE_ROUTE} />,
        <Route exact path={ARTICLE_ROUTE} component={ArticlePage} key={ARTICLE_ROUTE} />,
        <Route exact path={CATEGORIES_ROUTE} component={CategoriesPage} key={CATEGORIES_ROUTE} />,
        <Route exact path={ADD_ROUTE} component={EditorPage} key={"editorPage"} />,
        <Route exact path={EDIT_ROUTE} component={EditorPage} key={"editorPage"} />,
    ];
}
