/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

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
