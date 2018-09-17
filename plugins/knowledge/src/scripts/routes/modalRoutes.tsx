/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { Route } from "react-router-dom";
import Loading from "@knowledge/components/Loading";
import Loadable from "react-loadable";

const EditorPage = Loadable({
    loading: Loading,
    loader: () =>
        import(/* webpackChunkName: "plugins/knowledge/js/webpack/pages/kb/editor" */ "@knowledge/pages/editor/EditorPage"),
});

const modalRoutes = [
    <Route path="/kb/articles/add" component={EditorPage} />,
    <Route path="/kb/articles/:id/editor" component={EditorPage} />,
];

export function getModalRoutes() {
    return modalRoutes;
}
