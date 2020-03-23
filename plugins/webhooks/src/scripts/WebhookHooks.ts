/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import {
    WebhookStatus,
    IWebhook
} from "@webhooks/WebhookModel";
import { useEffect } from "react";
import { useSelector } from "react-redux";
import { LoadStatus, ILoadable, ILinkListData } from "@vanilla/library/src/scripts/@types/api/core";
import { IWebhookStoreState } from "@webhooks/WebhookReducer";
import { useWebhookActions } from "@webhooks/WebhookActions";

export function useWebhooks() {
    const { getAll } = useWebhookActions();
    const webhooksByID = useSelector((state: IWebhookStoreState) => state.webhooks.webhooksByID);

    useEffect(() => {
        if (webhooksByID.status === LoadStatus.PENDING) {
            void getAll();
        }
    }, [getAll, webhooksByID]);

    return webhooksByID;
}


