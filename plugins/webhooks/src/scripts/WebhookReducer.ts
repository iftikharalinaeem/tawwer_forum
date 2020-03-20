/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { produce } from "immer";
import { reducerWithInitialState } from "typescript-fsa-reducers";
import { IWebhook } from "./WebhookModel";
import { ILoadable, LoadStatus } from "@vanilla/library/src/scripts/@types/api/core";
import { WebhookActions } from "@webhooks/WebhookActions";
import { RoleActions } from "@dashboard/roles/RoleActions";

export interface IWebhookState {
    webhooksByID: ILoadable<Record<number, IWebhook>>;
}

export interface IWebhookStoreState {
    webhooks: IWebhookState;
}

export const INITIAL_WEBHOOK_STATE: IWebhookState = {
    webhooksByID: {
        status: LoadStatus.PENDING,
    },
};

export const WebhookReducer = produce(
    reducerWithInitialState<IWebhookState>(INITIAL_WEBHOOK_STATE)
        .case(WebhookActions.getAllWebhookACs.started, (state, action) => {
            state.webhooksByID = {
                status: LoadStatus.LOADING,
            };
            return state;
        })
        .case(WebhookActions.getAllWebhookACs.done, (state, payload) => {
            const webhooksByID: Record<number, IWebhook> = {};
            payload.result.forEach(wehook => {
                webhooksByID[wehook.webhookID] = wehook;
            });
            state.webhooksByID = {
                status: LoadStatus.SUCCESS,
                data: webhooksByID,
            };
            return state;
        })
        .case(RoleActions.getAllACs.failed, (nextState, action) => {
            nextState.webhooksByID.status = LoadStatus.ERROR;
            nextState.webhooksByID.error = action.error;
            return nextState;
        }),
);
