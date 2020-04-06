/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import { produce } from "immer";
import { reducerWithInitialState } from "typescript-fsa-reducers";
import { IDelivery, IDeliveryState, IDeliveryFragment, INITIAL_DELIVERY_STATE } from "./DeliveryTypes";
import { LoadStatus } from "@vanilla/library/src/scripts/@types/api/core";
import { DeliveryActions } from "@webhooks/DeliveryActions";

export const DeliveryReducer = produce(
    reducerWithInitialState<IDeliveryState>(INITIAL_DELIVERY_STATE)
        .case(DeliveryActions.getAllDeliveryACs.started, (state, action) => {
            state.deliveriesByWebhookID = {
                status: LoadStatus.LOADING,
            };
            return state;
        })
        .case(DeliveryActions.getAllDeliveryACs.failed, (state, action) => {
            state.deliveriesByWebhookID.status = LoadStatus.ERROR;
            state.deliveriesByWebhookID.error = action.error;
            return state;
        })
        .case(DeliveryActions.getAllDeliveryACs.done, (state, payload) => {
            const deliveriesByWebhookID: Record<number, IDeliveryFragment> = {};
            payload.result.forEach(delivery => {
                //alert(JSON.stringify(payload, null, 4));
                if (delivery.webhookDeliveryID) {
                    //alert(JSON.stringify(delivery, null, 4));
                    deliveriesByWebhookID[delivery.webhookDeliveryID] = delivery;
                }
            });
            state.deliveriesByWebhookID = {
                status: LoadStatus.SUCCESS,
                data: deliveriesByWebhookID,
            };
            return state;
        })
    );
        