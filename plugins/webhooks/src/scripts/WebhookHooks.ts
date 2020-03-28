/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IWebhookStoreState } from "@webhooks/WebhookTypes";
import { useEffect } from "react";
import { useSelector } from "react-redux";
import { LoadStatus } from "@vanilla/library/src/scripts/@types/api/core";
import { useWebhookActions } from "@webhooks/WebhookActions";

export function useWebhooks() {
    const webhooksByID = useSelector((state: IWebhookStoreState) => state.webhooks.webhooksByID);
    const { getAll } = useWebhookActions();

    useEffect(() => {
        if (webhooksByID.status === LoadStatus.PENDING) {
            void getAll();
        }
    }, [getAll, webhooksByID]);

    return webhooksByID;
}

export function useWebhookData() {
    const debug = useSelector((state: IWebhookStoreState) => state.webhooks);
    return useSelector((state: IWebhookStoreState) => state.webhooks);
}
