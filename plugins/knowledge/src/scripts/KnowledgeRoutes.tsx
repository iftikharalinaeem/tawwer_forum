/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { BrowserRouter, Route } from "react-router-dom";
import Loadable from "react-loadable";
import { t } from "@library/application";

function Loading({ error, pastDelay, retry }) {
    if (error) {
        return (
            <div>
                {t("Error! ")}
                <button onClick={retry}>{t("Retry")}</button>
            </div>
        );
    } else if (pastDelay) {
        return <div>{t("Loading...")}</div>;
    } else {
        return null;
    }
}

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

export default function KnowledgeRoutes() {
    return (
        <BrowserRouter>
            <React.Fragment>
                <Route exact path="/kb" component={HomePage} />
                <Route path="/kb/articles/:slug" component={ArticlePage} />
            </React.Fragment>
        </BrowserRouter>
    );
}
