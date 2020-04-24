/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import ModalLoader from "@library/modal/ModalLoader";
import RouteHandler from "@library/routing/RouteHandler";
import { makeThemeEditorUrl } from "./makeThemeEditorUrl";
const themeEditorPaths = [
    "/theme/theme-settings/add",
    "/theme/theme-settings/:id(\\d+)/edit",
    "/theme/theme-settings/preview",
];

//Editor
const THEME_EDITOR_KEY = "ThemeEditorPageKey";

const loadEditor = () => import(/* webpackChunkName: "pages/resourceAddEdit" */ "@themingapi/theme/ThemeEditorPage");
export const ThemeEditorRoute = new RouteHandler(
    loadEditor,
    themeEditorPaths,
    makeThemeEditorUrl,
    ModalLoader,
    THEME_EDITOR_KEY,
);

const loadRevisions = () =>
    import(/* webpackChunkName: "pages/themeRevisions" */ "@themingapi/theme/ThemeRevisionsPage");
export const ThemeRevisionsRoute = new RouteHandler(
    loadRevisions,
    "/theme/theme-settings/:id/revisions",
    (data: { themeID: string | number }) => `/theme/theme-settings/${data.themeID}/revisions`,
    ModalLoader,
);

export const ThemePreviewRoute = new RouteHandler(
    () => import(/* webpackChunkName: "pages/themePreview" */ "@themingapi/theme/ThemeEditorPreviewPage"),
    "/theme/theme-settings/:id/preview",
    (data?: { themeID: string | number }) => `/theme/theme-settings/:id/preview`,
);

export function getThemeRoutes() {
    return [ThemePreviewRoute.route, ThemeEditorRoute.route, ThemeRevisionsRoute.route];
}
