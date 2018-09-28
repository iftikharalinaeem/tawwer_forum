/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { Route } from "react-router-dom";
import Loadable from "react-loadable";
import { ModalLoader } from "@library/components/modal";
import { ADD_EDIT_ROUTE } from "@knowledge/modules/editor/route";

/** A loadable version of the Editor Page */
const ModalEditorPage = Loadable({
    loading: ModalLoader,
    loader: () => import(/* webpackChunkName: "pages/kb/editor" */ "@knowledge/modules/editor/EditorPage"),
});

/**
 * Get the data for routes that can render in a modal.
 *
 * We can't return actual react components here because the React Router <Switch>
 * only looks at its direct children. Trying to join separate components of routes using
 * <React.Fragment> does not currently work
 */
export function getModalRoutes(): JSX.Element[] {
    return [<Route path={ADD_EDIT_ROUTE} component={ModalEditorPage} />];
}
