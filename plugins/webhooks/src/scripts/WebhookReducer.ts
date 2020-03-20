/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { produce } from "immer";
import { reducerWithInitialState } from "typescript-fsa-reducers";
import { IWebhook } from "./WebhookModel";
import { ILoadable, LoadStatus } from "@vanilla/library/src/scripts/@types/api/core";
import { WebhookActions } from "@webhooks/WebhookActions";

export interface IWebhookState {
    webhooks: ILoadable<Record<number, IWebhook>>;
}

export const INITIAL_WEBHOOK_STATE: IWebhookState = {
    webhooks: {
        status: LoadStatus.PENDING,
        data: [],
    },
};

export const WebhookReducer = produce(
    reducerWithInitialState<IWebhookState>(INITIAL_WEBHOOK_STATE)
        .case(WebhookActions.getWebhook_ACs.started, (state, action) => {
            state.webhooks = {
                status: LoadStatus.LOADING,
            };
            console.log("WebhookReducer started");
            return state;
        })
        .case(WebhookActions.getWebhook_ACs.done, (state, payload) => {
            // const webhooks: Record<number, IWebhook> = {};
            // payload.result.forEach(webhook => {
            //     webhooks[webhook.webhookID] = webhook;
            // });
            // state.webhooks = {
            //     status: LoadStatus.SUCCESS,
            //     data: webhooks,
            // };

            state.webhooks = {
                status: LoadStatus.SUCCESS,
                data: payload.result,
            };

            console.log("payload.result");
            console.log(payload.result);
            return state;
        })
        .case(WebhookActions.getWebhook_ACs.failed, (state, action) => {
            state.webhooks.status = LoadStatus.ERROR;
            state.webhooks.error = action.error;
            return state;
        }),
);
