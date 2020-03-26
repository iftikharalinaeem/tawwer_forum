/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import { produce } from "immer";
import { reducerWithInitialState } from "typescript-fsa-reducers";
import { IWebhook } from "./WebhookTypes";
import { ILoadable, LoadStatus } from "@vanilla/library/src/scripts/@types/api/core";
import { WebhookActions } from "@webhooks/WebhookActions";
import { IWebhookState, INITIAL_WEBHOOK_STATE } from "@webhooks/WebhookTypes";

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
        .case(WebhookActions.getAllWebhookACs.failed, (state, action) => {
            state.webhooksByID.status = LoadStatus.ERROR;
            state.webhooksByID.error = action.error;
            return state;
        })
        .case(WebhookActions.updateFormAC, (state, payload) => {
            state.form = {
                ...state.form,
                ...payload,
            };
            return state;
        })
);
