/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import Loadable from "react-loadable";
import Loading from "@knowledge/components/Loading";

export const EditorPage = Loadable({
    loading: Loading,
    loader: () =>
        import(/* webpackChunkName: "plugins/knowledge/js/webpack/pages/kb/editor" */ "@knowledge/pages/editor/EditorPage"),
});

export const ArticlePage = Loadable({
    loading: Loading,
    loader: () =>
        import(/* webpackChunkName: "plugins/knowledge/js/webpack/pages/kb/article" */ "@knowledge/pages/article/ArticlePage"),
});

export const HomePage = Loadable({
    loading: Loading,
    loader: () =>
        import(/* webpackChunkName: "plugins/knowledge/js/webpack/pages/kb/index" */ "@knowledge/pages/home/HomePage"),
});
