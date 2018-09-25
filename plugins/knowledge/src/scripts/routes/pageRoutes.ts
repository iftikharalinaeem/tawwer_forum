/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import { RouteProps } from "react-router-dom";
import Loadable from "react-loadable";
import { getModalRouteData } from "@knowledge/routes/modalRoutes";
import FullPageLoader from "@library/components/FullPageLoader";
import { constants as articleConstants } from "@knowledge/modules/article/state";

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

/** The route data for routes that appear in only the in the normal page. */
const pageRoutes: RouteProps[] = [
    {
        exact: true,
        path: "/kb",
        component: HomePage,
    },
    {
        path: articleConstants.ARTICLE_ROUTE,
        component: ArticlePage,
    },
];

/**
 * Get the data for routes that can render in a modal.
 *
 * If a data can also be shown in the Modal container place it in {@link getModalRouteData}
 *
 * We can't return actual react components here because the React Router <Switch>
 * only looks at its direct children. Trying to join separate components of routes using
 * <React.Fragment> does not currently work.
 *
 * @param isCurrentRouteModal Whether or not the current route is
 *
 * @returns Data can that can be passed into a Route component.
 */
export function getPageRouteData(isCurrentRouteModal: boolean) {
    if (isCurrentRouteModal) {
        return pageRoutes;
    } else {
        return [...pageRoutes, ...getModalRouteData(false)];
    }
}
