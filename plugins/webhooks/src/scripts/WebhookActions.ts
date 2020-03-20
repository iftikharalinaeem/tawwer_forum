/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import ReduxActions, { bindThunkAction, useReduxActions } from "@library/redux/ReduxActions";
import { IApiError } from "@library/@types/api/core";
import { actionCreatorFactory } from "typescript-fsa";
import { IWebhook } from "@webhooks/WebhookModel";

const actionCreator = actionCreatorFactory("@@webhooks");
type IGetWebhooksRequest = {
    wehookID: number;
};

export class WebhookActions extends ReduxActions {
    public static getWebhook_ACs = actionCreator.async<IGetWebhooksRequest, IWebhook[], IApiError>("GET");

    public getAll = () => {
        const thunk = bindThunkAction(WebhookActions.getWebhook_ACs, async () => {
            const response = await this.api.get(`/webhooks`, {});
            console.log("getAll response");
            console.log(response);
            return response.data;
        });
        return this.dispatch(thunk);
    };
}

export function useWebhookActions() {
    return useReduxActions(WebhookActions);
}
