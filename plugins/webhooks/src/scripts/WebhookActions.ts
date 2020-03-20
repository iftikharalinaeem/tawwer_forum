/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import ReduxActions, { bindThunkAction, useReduxActions } from "@library/redux/ReduxActions";
import { IApiError } from "@library/@types/api/core";
import { IWebhook } from "@webhooks/WebhookModel";
import actionCreatorFactory from "typescript-fsa";

const createAction = actionCreatorFactory("@@webhooks");

export class WebhookActions extends ReduxActions {
    public static readonly getAllWebhookACs = createAction.async<{}, IWebhook[], IApiError>("GET");

    public getAll = () => {
        const thunk = bindThunkAction(WebhookActions.getAllWebhookACs, async () => {
            const response = await this.api.get(`/webhooks`, {});
            return response.data;
        })();
        return this.dispatch(thunk);
    };
}

export function useWebhookActions() {
    return useReduxActions(WebhookActions);
}
