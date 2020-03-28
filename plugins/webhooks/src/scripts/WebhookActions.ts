/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import ReduxActions, { bindThunkAction } from "@library/redux/ReduxActions";
import { IApiError } from "@library/@types/api/core";
import {
    IWebhook,
    IWebhookFormState,
    IPatchWebhookRequest,
    IPostWebhookRequest,
    IWebhookState,
    INITIAL_WEBHOOK_FORM,
    IWebhookStoreState,
} from "@webhooks/WebhookTypes";
import actionCreatorFactory from "typescript-fsa";
import { useDispatch } from "react-redux";
import { useMemo } from "react";
import apiv2 from "@library/apiv2";

const actionCreator = actionCreatorFactory("@@webhooks");

interface IWebhookParams {
    webhookID: number;
}
interface IGetWebhookRequest {
    webhookID: number;
}
type IDeleteWebhookRequest = {
    webhookID: number;
};

type IGetWebhookResponse = IWebhook[];
type IPostWebhookResponse = IWebhook;
type IPatchWebhookResponse = IWebhook;
type IDeleteWebhookResponse = undefined;

export class WebhookActions extends ReduxActions {
    public static readonly getAllWebhookACs = actionCreator.async<{}, IWebhook[], IApiError>("GET");
    public static readonly getEditWebhookACs = actionCreator.async<{webhookID: number}, IWebhook[], IApiError>("GET_EDIT");
    public static postWebhookACs = actionCreator.async<IPostWebhookRequest, IPostWebhookResponse, IApiError>("POST");
    
    public getAll = () => {
        const thunk = bindThunkAction(WebhookActions.getAllWebhookACs, async () => {
            const response = await this.api.get(`/webhooks`, {});
            return response.data;
        })();   
        return this.dispatch(thunk);
    };

    public getEdit = (webhookID: number): Promise<IWebhook | null> => {
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

    public static updateFormAC = actionCreator<Partial<IWebhookFormState>>("UPDATE_FORM");
    public updateForm = this.bindDispatch(WebhookActions.updateFormAC);

    public saveWebhookForm = async (form: IWebhookFormState) => {
        if (form.webhookID) {
            //return await this.patchWebhook(form as any);
        } else {
            return await this.postWebhook(form as any);
        }
    };

    public postWebhook(options: IPostWebhookRequest) {
        const thunk = bindThunkAction(WebhookActions.postWebhookACs, async () => {
            console.log('options');
            console.log(options);
            const response = await this.api.post(`/webhooks/`, options);
            return response.data;
        })(options);

        return this.dispatch(thunk);
    };

    // public patchWebhook(options: IPatchWebhookRequest) {
    //     const { webhookID, ...url } = options;

    //     const thunk = bindThunkAction(WebhookActions.patchWebhook_ACs, async () => {
    //         const response = await this.api.patch(`/webhook-settings/${webhookID}`, url);
    //         return response.data;
    //     })(options);

    //     return this.dispatch(thunk);
    // }
    public static deleteWebhook_ACs = actionCreator.async<
        IDeleteWebhookRequest,
        IDeleteWebhookResponse,
        IApiError
    >("DELETE");

    public deleteWebhook = (options: IDeleteWebhookRequest) => {
        const apiThunk = bindThunkAction(WebhookActions.deleteWebhook_ACs, async () => {
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
