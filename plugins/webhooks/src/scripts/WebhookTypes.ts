/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import { useSelector } from "react-redux";
import { ILoadable, LoadStatus } from "@vanilla/library/src/scripts/@types/api/core";
import { ICoreStoreState } from "@library/redux/reducerRegistry";

/**
 * Interface representing a webhook base resource.
 */

export interface IWebhook {
    webhookID: number;
    status: WebhookStatus;
    name: string;
    events: string;
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
export interface IPatchWebhookRequest {
    webhookID: number;
    status?: string;
    events: string;
    name: string;
    url: string;
    secret: string;
   
}
export interface IPostWebhookRequest {
    description: string;
    status?: string;
    events: string;
    name: string;
    url: string;
    secret: string;
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
    webhookID: number;
    status: WebhookStatus;
    name: string;
    events: string;
    url: string;
    secret: string;
}

export const INITIAL_WEBHOOK_FORM: IWebhookFormState = {
    webhookID: 0,
    status: WebhookStatus.ACTIVE,
    name: "",
    events: JSON.stringify([EventType.ALL]),
    url: "",
    secret: "",
};

export const INITIAL_WEBHOOK_STATE: IWebhookState = {
    webhooksByID: {
        status: LoadStatus.PENDING,
        deletesByID: ''
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
