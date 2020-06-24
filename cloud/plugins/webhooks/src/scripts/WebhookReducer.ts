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
        // GET
        .case(WebhookActions.getAllWebhookACs.started, (state, action) => {
            state.webhooksByID = {
                status: LoadStatus.LOADING,
            };
            return state;
        })
        .case(WebhookActions.getAllWebhookACs.failed, (state, action) => {
            state.webhooksByID.status = LoadStatus.ERROR;
            state.webhooksByID.error = action.error;
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

        // GET_EDIT
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

        // POST
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
            if (payload.result.webhookID) {
                state.webhooksByID.data![payload.result.webhookID] = payload.result;
                state.formSubmitByID[payload.result.webhookID] = {
                    status: LoadStatus.SUCCESS,
                };
            }
            return state;
        })
        .case(WebhookActions.clearFormAC, (state, { webhookID }) => {
            delete state.formSubmitByID[webhookID];
            return state;
        })
        .case(WebhookActions.clearErrorAC, state => {
            state.formSubmit = {
                status: LoadStatus.PENDING,
            };
            return state;
        })

        // PATCH
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
            if (payload.result.webhookID) {
                state.webhooksByID.data![payload.result.webhookID] = payload.result;
                state.formSubmitByID[payload.result.webhookID] = {
                    status: LoadStatus.SUCCESS,
                };
            }
            return state;
        })

        // DELETE
        .case(WebhookActions.deleteWebhookACs.started, (state, payload) => {
            state.deletesByID[payload.webhookID] = {
                status: LoadStatus.LOADING,
            };
            return state;
        })
        .case(WebhookActions.deleteWebhookACs.failed, (state, payload) => {
            state.deletesByID[payload.params.webhookID] = {
                status: LoadStatus.ERROR,
                error: payload.error,
            };
            return state;
        })
        .case(WebhookActions.deleteWebhookACs.done, (state, payload) => {
            delete state.webhooksByID.data![payload.params.webhookID];
            state.deletesByID[payload.params.webhookID] = {
                status: LoadStatus.SUCCESS,
            };
            return state;
        })
        .case(WebhookActions.clearDeleteStatus, (state, { webhookID }) => {
            delete state.deletesByID[webhookID];
            return state;
        }),
);
