/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import { useSelector } from "react-redux";
import { ILoadable, LoadStatus } from "@vanilla/library/src/scripts/@types/api/core";

/**
 * Interface representing a webhook base resource.
 */
export interface IWebhook {
    webhookID: number;
    status: string;
    name: string;
    events: [];
    url: string;
    secret: string;
    dateInserted: string;
    insertUserID: number;
    dateUpdated: string;
    updateUserID: number;
}

export enum WebhookStatus {
    ACTIVE = "active",
    DISABLED = "disabled",
}

export enum WebhooksEventsSelect {
    ALL = "all",
    INDIVIDUAL = "individual",
}

export interface IWebhookState {
    webhooksByID: ILoadable<{
        [id: number]: IWebhook;
    }>;
    form: IWebhookFormState;
    formSubmit: ILoadable<{}>;
}

export interface IWebhookFormState {
    webhookID: number;
    status: string;
    name: string;
    events: [];
    url: string;
    secret: string;
}

export const INITIAL_WEBHOOK_FORM: IWebhookFormState = {
    webhookID: 0,
    status: "",
    name: "",
    events: [],
    url: "",
    secret: "",
};

export const INITIAL_WEBHOOK_STATE: IWebhookState = {
    webhooksByID: {
        status: LoadStatus.PENDING,
    },
    form: INITIAL_WEBHOOK_FORM,
    formSubmit: {
        status: LoadStatus.PENDING,
    },
};

export interface IWebhookStoreState {
    webhooks: IWebhookState;
}

export function useWebhookData() {
    return useSelector((state: IWebhookStoreState) => state.webhooks);
}
