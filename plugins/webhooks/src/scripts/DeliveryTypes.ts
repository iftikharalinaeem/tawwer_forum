/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import { ILoadable, LoadStatus } from "@vanilla/library/src/scripts/@types/api/core";

export interface IDeliveryStore {
    deliveries: IDeliveryState;
}

export interface IDeliveryState {
    deliveriesByWebhookID: ILoadable<{
        [webhookID: number]: IDeliveryFragment;
    }>;
    deliveriesByDeliveryID: {
        [deliveryID: string]: ILoadable<IDelivery>;
    };
}

export interface IDeliveryFragment {
    webhookDeliveryID: string;
    webhookID: number;
    requestDuration: number;
    responseCode: number;
    dateInserted: string;
}

export interface IDelivery extends IDeliveryFragment {
    requestBody: string;
    requestHeaders: Record<string, string>;
    responseBody: string;
    responseHeaders: Record<string, string>;
}

export const INITIAL_DELIVERY_STATE: IDeliveryState = {
    deliveriesByWebhookID: {
        status: LoadStatus.PENDING,
    },
    deliveriesByDeliveryID: {},
};
