/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import { RouteProps } from "react-router-dom";
import Loadable from "react-loadable";
import ModalLoader from "@knowledge/components/ModalLoader";
import FullPageLoader from "@library/components/FullPageLoader";

/** A loadable version of the Editor Page */
const EditorPage = Loadable({
    loading: FullPageLoader,
    loader: () => import(/* webpackChunkName: "pages/kb/editor" */ "@knowledge/pages/editor/EditorPage"),
});

/** A loadable version of the Editor Page */
const ModalEditorPage = Loadable({
    loading: ModalLoader,
    loader: () => import(/* webpackChunkName: "pages/kb/editor" */ "@knowledge/pages/editor/EditorPage"),
});

/**
 * Get the data for routes that can render in a modal.
 *
 * We can't return actual react components here because the React Router <Switch>
 * only looks at its direct children. Trying to join separate components of routes using
 * <React.Fragment> does not currently work.
 *
 * @param forModal Whether or not theses routes will be displaying in a modal or not.
 *
 * @returns Data can that can be passed into a Route component.
 */
export function getModalRouteData(forModal: boolean = true): RouteProps[] {
    return [
        {
            path: "/kb/articles/add",
            component: forModal ? ModalEditorPage : EditorPage,
        },
        {
            path: "/kb/articles/:id/editor",
            component: forModal ? ModalEditorPage : EditorPage,
        },
    ];
}
