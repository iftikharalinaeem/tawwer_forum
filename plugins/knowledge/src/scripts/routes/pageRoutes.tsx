/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { Redirect, Route } from "react-router-dom";
import Loadable from "react-loadable";
import FullPageLoader from "@library/components/FullPageLoader";
import { ADD_ROUTE, EDIT_ROUTE, REVISIONS_ROUTE } from "@knowledge/modules/editor/route";
import ErrorPage from "@knowledge/routes/ErrorPage";
import { LoadStatus } from "@library/@types/api";
import { ModalLoader } from "@library/components/modal";
import { exact } from "prop-types";

export const ARTICLE_ROUTE = "/kb/articles/:id(\\d+)(-[^/]+)?";

export const CATEGORIES_ROUTE = "/kb/categories/:id(\\d+)(-[^/]+)?";
export const SEARCH_ROUTE = "/kb/search";
export const DRAFTS_ROUTE = "/kb/drafts";

/** A loadable version of the Editor Page */
const EditorPage = Loadable({
    loading: ModalLoader,
    loader: () => import(/* webpackChunkName: "pages/kb/editor" */ "@knowledge/modules/editor/EditorPage"),
});

/** A loadable version of the article revisions page. */
const RevisionsPage = Loadable({
    loading: ModalLoader,
    loader: () => import(/* webpackChunkName: "pages/kb/revisions" */ "@knowledge/modules/editor/RevisionsPage"),
});

/** A loadable version of the article page. */
const ArticlePage = Loadable({
    loading: FullPageLoader,
    loader: () => import(/* webpackChunkName: "pages/kb/article" */ "@knowledge/modules/article/ArticlePage"),
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

/** A loadable version of the search page. */
const SearchPage = Loadable({
    loading: FullPageLoader,
    loader: () => import(/* webpackChunkName: "pages/kb/search" */ "@knowledge/modules/search/SearchPage"),
});

/** A loadable version of the search page. */
const DraftsPage = Loadable({
    loading: ModalLoader,
    loader: () => import(/* webpackChunkName: "pages/kb/drafts" */ "@knowledge/modules/drafts/DraftsPage"),
});

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
        <Redirect exact path="/kb" to={SEARCH_ROUTE} key={SEARCH_ROUTE} />,
        <Route exact path={ARTICLE_ROUTE} component={ArticlePage} key={ARTICLE_ROUTE} />,
        <Route exact path={CATEGORIES_ROUTE} component={CategoriesPage} key={CATEGORIES_ROUTE} />,
        <Route exact path={ADD_ROUTE} component={EditorPage} key={"editorPage"} />,
        <Route exact path={EDIT_ROUTE} component={EditorPage} key={"editorPage"} />,
        <Route exact path={REVISIONS_ROUTE} component={RevisionsPage} key={REVISIONS_ROUTE} />,
        <Route exact path={SEARCH_ROUTE} component={SearchPage} key={SEARCH_ROUTE} />,
        <Route exact path={DRAFTS_ROUTE} component={DraftsPage} key={DRAFTS_ROUTE} />,
        <Route component={NotFound} key={"not found"} />,
    ];
}
