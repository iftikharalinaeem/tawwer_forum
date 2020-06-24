/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import RouteHandler from "@vanilla/library/src/scripts/routing/RouteHandler";
import { IWebhook } from "@webhooks/WebhookTypes";

export const WebhooksIndexRoute = new RouteHandler(
    () => import(/* webpackChunkName: "pages/webhooks" */ "@webhooks/webhooksIndex/WebhooksIndexPage"),
    "/webhook-settings",
    (webhooks: { webhooks?: IWebhook }) => "/webhook-settings",
);

export const WebhooksAddEditRoute = new RouteHandler(
    () => import(/* webpackChunkName: "pages/webhooks/addEdit" */ "@webhooks/WebhookAddEdit"),
    ["/webhook-settings/:webhookID/edit", "/webhook-settings/add"],
    (params: { webhookID?: number }) =>
        params.webhookID != null ? `/webhook-settings/${params.webhookID}/edit` : "/webhook-settings/add/",
);

export const DeliveriesIndexRoute = new RouteHandler(
    () => import(/* webpackChunkName: "pages/webhooks/deliveries" */ "@webhooks/deliveryIndex/DeliveryIndexPage"),
    ["/webhook-settings/:webhookID/deliveries", "/webhook-settings/:webhookID/deliveries/p:page(\\d+)"],
    (params: { webhookID?: number; page?: number }) => `/webhook-settings/${params.webhookID}/deliveries`,
);

export const DeliveryRoute = new RouteHandler(
    () => import(/* webpackChunkName: "pages/webhooks/delivery" */ "@webhooks/deliveryIndex/DeliveryIndexPage"),
    "/webhook-settings/deliveries/:deliveryID",
    (params: { deliveryID?: number }) => `/webhook-settings/deliveries/${params.deliveryID}`,
);

export const allWebhookRoutes = [
    DeliveryRoute.route,
    DeliveriesIndexRoute.route,
    WebhooksAddEditRoute.route,
    WebhooksIndexRoute.route,
];
