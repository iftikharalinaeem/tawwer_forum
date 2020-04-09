/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IDeliveryFragment, IDeliveryStore, IDelivery } from "@webhooks/DeliveryTypes";
import { useEffect } from "react";
import { useSelector } from "react-redux";
import { ILoadable, LoadStatus } from "@vanilla/library/src/scripts/@types/api/core";
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

export function useDelivery(webhookID?: number, deliveryID?: string): ILoadable<{ [id: number]: IDelivery }> {
    const deliveriesByDeliveryID = useSelector((state: IDeliveryStore) => state.deliveries.deliveriesByDeliveryID);
    const { getDeliveryByID } = useDeliveryActions();

    useEffect(() => {
        if (deliveriesByDeliveryID.status === LoadStatus.PENDING && deliveryID) {
            void getDeliveryByID(webhookID, deliveryID);
        }
    }, [getDeliveryByID, deliveriesByDeliveryID, deliveryID]);

    return deliveriesByDeliveryID;
}

export function useDeliveryData() {
    return useSelector((state: IDeliveryStore) => state.deliveries);
}
