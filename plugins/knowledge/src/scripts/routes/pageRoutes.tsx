/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { Route } from "react-router-dom";
import Loading from "@knowledge/components/Loading";
import Loadable from "react-loadable";
import { getModalRoutes } from "@knowledge/routes/modalRoutes";

const ArticlePage = Loadable({
    loading: Loading,
    loader: () =>
        import(/* webpackChunkName: "plugins/knowledge/js/webpack/pages/kb/article" */ "@knowledge/pages/article/ArticlePage"),
});

const HomePage = Loadable({
    loading: Loading,
    loader: () =>
        import(/* webpackChunkName: "plugins/knowledge/js/webpack/pages/kb/index" */ "@knowledge/pages/home/HomePage"),
});

const pageRoutes = [
    <Route path="/kb" exact component={HomePage} />,
    <Route path="/kb/articles/(.*)-:id(\d+)" component={ArticlePage} />,
];

export function getPageRoutes(isCurrentRouteModal: boolean) {
    if (isCurrentRouteModal) {
        return pageRoutes;
    } else {
        return [...pageRoutes, ...getModalRoutes()];
    }
}
