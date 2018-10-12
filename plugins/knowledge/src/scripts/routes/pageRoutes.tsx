/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { Route } from "react-router-dom";
import Loadable from "react-loadable";
import FullPageLoader from "@library/components/FullPageLoader";
import { constants as categoriesConstants } from "@knowledge/modules/categories/state";
import { ADD_EDIT_ROUTE } from "@knowledge/modules/editor/route";
import CategoriesPage from "@knowledge/modules/categories/CategoriesPage";

export const ARTICLE_ROUTE = "/kb/articles/(.*)-:id(\\d+)";

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

/** A loadable version of the HomePage component. */
const HomePage = Loadable({
    loading: FullPageLoader,
    loader: () => import(/* webpackChunkName: "pages/kb/index" */ "@knowledge/modules/home/HomePage"),
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
        <Route path={ARTICLE_ROUTE} component={ArticlePage} key={13} />,
        <Route
            path={categoriesConstants.CATEGORIES_ROUTE}
            component={CategoriesPage}
            key={categoriesConstants.CATEGORIES_ROUTE}
        />,
        <Route path={ADD_EDIT_ROUTE} component={EditorPage} key={43} />,
    ];
}
