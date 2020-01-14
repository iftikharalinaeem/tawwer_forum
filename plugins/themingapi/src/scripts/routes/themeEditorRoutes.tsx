import React from "react";
import { Route } from "react-router-dom";
import ModalLoader from "@library/modal/ModalLoader";
import RouteHandler from "@library/routing/RouteHandler";

export const ThemeEditorRoute = new RouteHandler(
    () => import(/* webpackChunkName: "pages/resourceAddEdit" */ "@themingapi/theme/ThemeEditorPage"),
    ["/theme/theme-settings/add", "/theme/theme-settings/:id(\\d+)/edit"],
    (id?: number) => (id !== null ? `/theme/theme-settings/${id}/edit` : `/theme/theme-settings/add`),
    ModalLoader,
);
