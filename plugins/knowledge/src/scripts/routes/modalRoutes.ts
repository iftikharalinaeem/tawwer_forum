/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import { RouteProps } from "react-router-dom";
import Loadable from "react-loadable";
import FullPageLoader from "@library/components/FullPageLoader";

/** A loadable version of the Editor Page */
const EditorPage = Loadable({
    loading: FullPageLoader,
    loader: () =>
        import(/* webpackChunkName: "plugins/knowledge/js/webpack/pages/kb/editor" */ "@knowledge/pages/editor/EditorPage"),
});

/**
 * Get the data for routes that can render in a modal.
 *
 * We can't return actual react components here because the React Router <Switch>
 * only looks at its direct children. Trying to join separate components of routes using
 * <React.Fragment> does not currently work.
 *
 * @returns Data can that can be passed into a Route component.
 */
export function getModalRouteData(): RouteProps[] {
    return [
        {
            path: "/kb/articles/add",
            component: EditorPage,
        },
        {
            path: "/kb/articles/:id/editor",
            component: EditorPage,
        },
    ];
}
