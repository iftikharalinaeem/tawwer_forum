/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import { produce } from "immer";
import { reducerWithInitialState } from "typescript-fsa-reducers";
import { IWebhook } from "./WebhookTypes";
import { LoadStatus } from "@vanilla/library/src/scripts/@types/api/core";
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
                if (wehook.webhookID) {
                    webhooksByID[wehook.webhookID] = wehook;
                }
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

        .case(WebhookActions.getEditWebhookACs.started, (state, action) => {
            state.form.formStatus = LoadStatus.LOADING;
            return state;
        })
        .case(WebhookActions.getEditWebhookACs.failed, (state, action) => {
            state.form.formStatus = LoadStatus.ERROR;
            state.form.error = action.error;
            
            return state;
        })

        .case(WebhookActions.updateFormAC, (state, payload) => {
            state.form.formStatus = LoadStatus.SUCCESS;
            state.form = {
                ...state.form,
                ...payload,
            };
            return state;
        })

        .case(WebhookActions.postFormACs.started, (state, payload) => {
            state.formSubmit.status = LoadStatus.LOADING;
            return state;
        })
        .case(WebhookActions.postFormACs.failed, (state, payload) => {
            state.formSubmit.status = LoadStatus.ERROR;
            state.formSubmit.error = payload.error;
            return state;
        })
        .case(WebhookActions.postFormACs.done, (state, payload) => {
            state.formSubmit.status = LoadStatus.SUCCESS;
            state.webhooksByID = {
                status: LoadStatus.SUCCESS,
            };
            if (payload.result.webhookID) {
                state.webhooksByID[payload.result.webhookID] = payload.result;
            }
            return state;
        })

        .case(WebhookActions.patchFormACs.started, (state, payload) => {
            state.formSubmit.status = LoadStatus.LOADING;
            return state;
        })
        .case(WebhookActions.patchFormACs.failed, (state, payload) => {
            state.formSubmit.status = LoadStatus.ERROR;
            state.formSubmit.error = payload.error;
            return state;
        })
        .case(WebhookActions.patchFormACs.done, (state, payload) => {
            state.formSubmit.status = LoadStatus.SUCCESS;
            state.webhooksByID = {
                status: LoadStatus.SUCCESS,
            };
            if (payload.result.webhookID) {
                state.webhooksByID[payload.result.webhookID] = payload.result;
            }
            return state;
        })
);
