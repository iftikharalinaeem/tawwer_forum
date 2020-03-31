/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import ReduxActions, { bindThunkAction } from "@library/redux/ReduxActions";
import { IApiError } from "@library/@types/api/core";
import {
    IWebhook,
    IWebhookFormState,
    INITIAL_WEBHOOK_FORM,
} from "@webhooks/WebhookTypes";
import actionCreatorFactory from "typescript-fsa";
import { useDispatch } from "react-redux";
import { useMemo } from "react";
import apiv2 from "@library/apiv2";

const actionCreator = actionCreatorFactory("@@webhooks");
type IDeleteWebhookRequest = {
    webhookID: number;
};
type IDeleteWebhookResponse = undefined;

export class WebhookActions extends ReduxActions {
    public static readonly getAllWebhookACs = actionCreator.async<{}, IWebhook[], IApiError>("GET_ALL");
    
    public static readonly getEditWebhookACs = actionCreator.async<{ webhookID: number }, IWebhook, IApiError>("GET_EDIT");
    
    public static postFormACs = actionCreator.async<IWebhook, IWebhook, IApiError>("POST");

    public static patchFormACs = actionCreator.async<IWebhook, IWebhook, IApiError>("PATCH");
    
    public static clearErrorAC = actionCreator("CLEAR_ERROR");
    public clearError = this.bindDispatch(WebhookActions.clearErrorAC);

    public static clearFormAC = actionCreator<{ webhookID: number }>("CLEAR_FORM");
    public clearForm = this.bindDispatch(WebhookActions.clearFormAC);

    public static updateFormAC = actionCreator<Partial<IWebhookFormState>>("UPDATE_FORM");
    public updateForm = this.bindDispatch(WebhookActions.updateFormAC);
    
    public static deleteWebhookACs = actionCreator.async<IDeleteWebhookRequest, IDeleteWebhookResponse, IApiError>("DELETE");
    public static clearDeleteStatus = actionCreator<{ webhookID: number }>("CLEAR_DELETE_STATUS");
    public clearDeleteStatus = this.bindDispatch(WebhookActions.clearDeleteStatus);

    public getAll = () => {
        const thunk = bindThunkAction(WebhookActions.getAllWebhookACs, async () => {
            const response = await this.api.get(`/webhooks`, {});
            return response.data;
        })();
        return this.dispatch(thunk);
    };

    public getEdit = (webhookID: number): Promise<IWebhookFormState> => {
        const thunk = bindThunkAction(WebhookActions.getEditWebhookACs, async () => {
            const response = await this.api.get(`/webhooks/${webhookID}/edit`, {});
            return response.data;
        })({ webhookID });
        return this.dispatch(thunk);
    };

    public initForm = async (webhookID?: number) => {
        if (!webhookID) {
            this.updateForm(INITIAL_WEBHOOK_FORM);
        } else {
            const payload = await this.getEdit(webhookID);
            this.updateForm(payload);
        }
    };

	public saveWebhookForm = async (form: IWebhookFormState) => {
 		if (form.webhookID) {
            return await this.patchWebhook(form as any);
        } else {
            return await this.postWebhook(form as any);
        }
    };
    
	public postWebhook(options: IWebhook) {
 		const thunk = bindThunkAction(WebhookActions.postFormACs, async () => {
            const response = await this.api.post(`/webhooks/`, options);
            return response.data;
        })(options);
        
        return this.dispatch(thunk);
     };

    public patchWebhook(options: IWebhook) {
        const { webhookID, ...url } = options;

        const thunk = bindThunkAction(WebhookActions.patchFormACs, async () => {
            const response = await this.api.patch(`/webhooks/${webhookID}`, url);
            return response.data;
        })(options);

        return this.dispatch(thunk);
    };

    public deleteWebhook = (options: { webhookID: number }) => {
        const apiThunk = bindThunkAction(WebhookActions.deleteWebhookACs, async () => {
            const response = await this.api.delete(`/webhooks/${options.webhookID}`);
            return response.data;
        })(options);
        return this.dispatch(apiThunk);
    };
}

export function useWebhookActions() {
    const dispatch = useDispatch();
    const actions = useMemo(() => new WebhookActions(dispatch, apiv2), [dispatch]);
    return actions;
}
