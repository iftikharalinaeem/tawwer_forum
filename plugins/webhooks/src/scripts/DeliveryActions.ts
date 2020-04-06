/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import ReduxActions, { bindThunkAction } from "@library/redux/ReduxActions";
import { IApiError } from "@library/@types/api/core";
import { IDeliveryFragment } from "@webhooks/DeliveryTypes";
import actionCreatorFactory from "typescript-fsa";
import { useDispatch } from "react-redux";
import { useMemo } from "react";
import apiv2 from "@library/apiv2";

const actionCreator = actionCreatorFactory("@@deliveries");

export class DeliveryActions extends ReduxActions {
    public static readonly getAllDeliveryACs = actionCreator.async<
        { webhookID: number },
        IDeliveryFragment[],
        IApiError
    >("GET_ALL");

    public getAll = (webhookID: number) => {
        const thunk = bindThunkAction(DeliveryActions.getAllDeliveryACs, async () => {
            const response = await this.api.get(`/webhooks/${webhookID}/deliveries`, {});
            return response.data;
        })();

        return this.dispatch(thunk);
    };
}

export function useDeliveryActions() {
    const dispatch = useDispatch();
    const actions = useMemo(() => new DeliveryActions(dispatch, apiv2), [dispatch]);
    return actions;
}
