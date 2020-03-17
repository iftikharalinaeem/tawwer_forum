/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { useEffect } from "react";
import RouteHandler from "@vanilla/library/src/scripts/routing/RouteHandler";
import { Route } from "react-router";

export const WebhooksIndexRoute = new RouteHandler(
    () => import(/* webpackChunkName: "pages/webhooks" */ "@webhooks/PlaceholderPage"),
    "/webhook-settings",
    () => "/webhook-settings",
);

export const WebhooksAddEditRoute = new RouteHandler(
    () => import(/* webpackChunkName: "pages/webhooks/addEdit" */ "@webhooks/PlaceholderPage"),
    ["/webhook-settings/:webhookID/edit", "/webhook-settings/add"],
    (params: { webhookID?: number }) =>
        params.webhookID != null ? `/webhook-settings/${params.webhookID}/edit` : "/webhook-settings/add",
);

export const DeliveriesIndexRoute = new RouteHandler(
    () => import(/* webpackChunkName: "pages/webhooks/deliveries" */ "@webhooks/PlaceholderPage"),
    ["/webhook-settings/:webhookID/deliveries", "/webhook-settings/:webhookID/deliveries/p:page(\\d+)"],
    (params: { webhookID?: number; page?: number }) => `/webhook-settings/${params.webhookID}/deliveries`,
);

export const DeliveryRoute = new RouteHandler(
    () => import(/* webpackChunkName: "pages/webhooks/delivery" */ "@webhooks/PlaceholderPage"),
    "/webhook-settings/deliveries/:deliveryID",
    (params: { deliveryID?: number }) => `/webhook-settings/deliveries/${params.deliveryID}`,
);

export const allWebhookRoutes = [
    DeliveryRoute.route,
    DeliveriesIndexRoute.route,
    WebhooksAddEditRoute.route,
    WebhooksIndexRoute.route,
];
