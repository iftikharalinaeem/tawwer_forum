/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IWebhook, IWebhookStore } from "@webhooks/WebhookTypes";
import { useEffect } from "react";
import { useSelector } from "react-redux";
import { ILoadable, LoadStatus } from "@vanilla/library/src/scripts/@types/api/core";
import { useWebhookActions } from "@webhooks/WebhookActions";

export function useWebhooks(): ILoadable<{ [id: number]: IWebhook }> {
    const webhooksByID = useSelector((state: IWebhookStore) => state.webhooks.webhooksByID);
    const { getAll } = useWebhookActions();

    useEffect(() => {
        if (webhooksByID.status === LoadStatus.PENDING) {
            void getAll();
        }
    }, [getAll, webhooksByID]);

    return webhooksByID;
}

export function useWebhookData() {
    return useSelector((state: IWebhookStore) => state.webhooks);
}