/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import { produce } from "immer";
import { reducerWithInitialState } from "typescript-fsa-reducers";
import { IDeliveryState, IDeliveryFragment, INITIAL_DELIVERY_STATE, IDelivery } from "./DeliveryTypes";
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
                if (delivery.webhookDeliveryID) {
                    deliveriesByWebhookID[delivery.webhookDeliveryID] = delivery;
                }
            });
            state.deliveriesByWebhookID = {
                status: LoadStatus.SUCCESS,
                data: deliveriesByWebhookID,
            };
            return state;
        })

        .case(DeliveryActions.getDeliveryByIDACs.started, (state, action) => {
            state.deliveriesByDeliveryID[action.deliveryID] = {
                status: LoadStatus.LOADING,
            };
            return state;
        })
        .case(DeliveryActions.getDeliveryByIDACs.failed, (state, action) => {
            if (action.params) {
                state.deliveriesByDeliveryID[action.params.deliveryID].status = LoadStatus.ERROR;
                state.deliveriesByDeliveryID[action.params.deliveryID].error = action.error;
            }
            return state;
        })
        .case(DeliveryActions.getDeliveryByIDACs.done, (state, payload) => {
            const deliveriesByDeliveryID: Record<number, IDelivery> = {};
            if (payload.result.webhookDeliveryID) {
                deliveriesByDeliveryID[payload.result.webhookDeliveryID] = payload.result;
            }
            state.deliveriesByDeliveryID[payload.result.webhookDeliveryID].status = LoadStatus.SUCCESS;
            state.deliveriesByDeliveryID[payload.result.webhookDeliveryID].data = payload.result;

            return state;
        }),
);