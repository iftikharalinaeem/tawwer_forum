/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IDeliveryFragment, IDeliveryStore } from "@webhooks/DeliveryTypes";
import { useEffect } from "react";
import { useSelector } from "react-redux";
import { ILoadable, LoadStatus } from "@vanilla/library/src/scripts/@types/api/core";
import { useDeliveryActions } from "@webhooks/DeliveryActions";

export function useDeliveries(): ILoadable<{ [id: number]: IDeliveryFragment }> {
    const deliveriesByWebhookID = useSelector((state: IDeliveryStore) => state.deliveries.deliveriesByWebhookID);
    //alert(JSON.stringify(deliveriesByWebhookID, null, 4));
    const { getAll } = useDeliveryActions();

    useEffect(() => {
        if (deliveriesByWebhookID.status === LoadStatus.PENDING) {
            void getAll();
        }
    }, [getAll, deliveriesByWebhookID]);

    return deliveriesByWebhookID;
}

export function useDeliveryData() {
    return useSelector((state: IDeliveryStore) => state.deliveries);
}
