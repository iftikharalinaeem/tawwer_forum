/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import ReduxActions, { bindThunkAction, useReduxActions } from "@library/redux/ReduxActions";
import { IApiError } from "@library/@types/api/core";
import { IWebhook } from "@webhooks/WebhookTypes";
import actionCreatorFactory from "typescript-fsa";
import {useDispatch} from "react-redux";
import {useMemo} from "react";
import apiv2 from "@library/apiv2";

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
    const dispatch = useDispatch();
    const actions = useMemo(() => new WebhookActions(dispatch, apiv2), [dispatch]);
    return actions;
}
