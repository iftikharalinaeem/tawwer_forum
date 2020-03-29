/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import { ILoadable, LoadStatus, IApiError } from "@vanilla/library/src/scripts/@types/api/core";

/**
 * Interface representing a webhook base resource.
 */
export interface IWebhook {
    webhookID: number | null;
    status: WebhookStatus;
    name: string;
    events: string[];
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

export enum EventType {
    ALL = "*",
    INDIVIDUAL = "individual",
    COMMENT = "comment",
    DISCUSSION = "discussion",
    USER = "user",
}
export interface IWebhookState {
    webhooksByID: ILoadable<{
        [id: number]: IWebhook;
    }>;
    form: IWebhookFormState;
    formSubmit: ILoadable<{}>;
    deletesByID: {
        [WebhookID: number]: ILoadable<{}>;
    };
}

export interface IWebhookFormState {
    webhookID: number | null;
    status: WebhookStatus;
    name: string;
    events: string[];
    url: string;
    secret: string;
    formStatus?: string;
    error?: IApiError;
}

export const INITIAL_WEBHOOK_FORM: IWebhookFormState = {
    webhookID: null,
    status: WebhookStatus.ACTIVE,
    name: "",
    events: [EventType.ALL],
    url: "",
    secret: "",
};

export const INITIAL_WEBHOOK_STATE: IWebhookState = {
    webhooksByID: {
        status: LoadStatus.PENDING,
        deletesByID: '',
    },
    form: INITIAL_WEBHOOK_FORM,
    formSubmit: {
        status: LoadStatus.PENDING,
    },
};

export interface IWebhookStoreState {
    webhooks: IWebhookState;
}
