/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IDeliveryFragment, IDeliveryStore, IDelivery } from "@webhooks/DeliveryTypes";
import { useEffect } from "react";
import { useSelector } from "react-redux";
import { ILoadable, LoadStatus, IApiError } from "@vanilla/library/src/scripts/@types/api/core";
import { useDeliveryActions } from "@webhooks/DeliveryActions";

export function useDeliveries(webhookID?: number): ILoadable<{ [id: number]: IDeliveryFragment }> {
    const deliveriesByWebhookID = useSelector((state: IDeliveryStore) => state.deliveries.deliveriesByWebhookID);
    const { getAll } = useDeliveryActions();

    useEffect(() => {
        if (deliveriesByWebhookID.status === LoadStatus.PENDING && webhookID) {
            void getAll(webhookID);
        }
    }, [getAll, deliveriesByWebhookID, webhookID]);

    return deliveriesByWebhookID;
}

export function useDelivery(
    webhookID?: number,
    deliveryID?: string,
): { [deliveryID: string]: ILoadable<IDelivery, IApiError> } {
    const deliveriesByDeliveryID = useSelector((state: IDeliveryStore) => state.deliveries.deliveriesByDeliveryID);
    const { getDeliveryByID } = useDeliveryActions();

    useEffect(() => {
        if (deliveryID && webhookID && deliveriesByDeliveryID[deliveryID].status === LoadStatus.PENDING) {
            void getDeliveryByID(webhookID, deliveryID);
        }
    }, [getDeliveryByID, deliveriesByDeliveryID, deliveryID, webhookID]);

    return deliveriesByDeliveryID;
}

export function useDeliveryData() {
    return useSelector((state: IDeliveryStore) => state.deliveries);
}