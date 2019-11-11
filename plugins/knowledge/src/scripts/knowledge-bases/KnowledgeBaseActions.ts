/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import ReduxActions, { bindThunkAction } from "@library/redux/ReduxActions";
import { actionCreatorFactory } from "typescript-fsa";
import {
    IKnowledgeBase,
    KnowledgeBaseStatus,
    IPostKnowledgeBaseRequest,
    IPatchKnowledgeBaseRequest,
} from "@knowledge/knowledge-bases/KnowledgeBaseModel";
import { IApiError } from "@library/@types/api/core";
import { useDispatch } from "react-redux";
import apiv2 from "@library/apiv2";
import { useMemo } from "react";
import { createAction } from "@library/redux/utility";
import { type } from "os";

const actionCreator = actionCreatorFactory("@@knowledgeBases");

type IGetKnowledgeBaseRequest = {
    kbID: number;
};
type IDeleteKnowledgeBaseRequest = {
    kbID: number;
};
type IGetKnowledgeBaseResponse = IKnowledgeBase[];
type IPostKnowledgeBaseResponse = IKnowledgeBase[];
type IPatchKnowledgeBaseResponse = IKnowledgeBase[];
type IDeleteKnowledgeBaseResponse = undefined;

/**
 * Actions for working with resources from the /api/v2/knowledge-bases endpoint.
 */
export default class KnowledgeBaseActions extends ReduxActions {
    public static readonly GET_ACS = actionCreator.async<{ status: KnowledgeBaseStatus }, IKnowledgeBase[], IApiError>(
        "GET_ALL",
    );

    public getAll = (status: KnowledgeBaseStatus = KnowledgeBaseStatus.PUBLISHED) => {
        const thunk = bindThunkAction(KnowledgeBaseActions.GET_ACS, async () => {
            const response = await this.api.get(`/knowledge-bases?expand=all&status=${status}`);
            console.log("==>", response.data);
            return response.data;
        })({ status });
        return this.dispatch(thunk);
    };

    public static getAllKB_ACs = actionCreator.async<IGetKnowledgeBaseRequest, IGetKnowledgeBaseResponse, IApiError>(
        "GET",
    );
    public static postKB_ACs = actionCreator.async<IPostKnowledgeBaseRequest, IPostKnowledgeBaseResponse, IApiError>(
        "POST",
    );
    public static patchKB_ACs = actionCreator.async<IPatchKnowledgeBaseRequest, IPatchKnowledgeBaseResponse, IApiError>(
        "PATCH",
    );
    public static deleteKB_ACs = actionCreator.async<
        IDeleteKnowledgeBaseRequest,
        IDeleteKnowledgeBaseResponse,
        IApiError
    >("DELETE");

    public getKBs(options: IGetKnowledgeBaseRequest) {
        const thunk = bindThunkAction(KnowledgeBaseActions.getAllKB_ACs, async () => {
            const { kbID, ...rest } = options;
            const params = { ...rest, expand: "all" };
            const response = await this.api.get(`/knowledge-bases/${options.kbID}`, { params });
            return response.data;
        })(options);

        return this.dispatch(thunk);
    }

    public postKB(options: IPostKnowledgeBaseRequest) {
        const thunk = bindThunkAction(KnowledgeBaseActions.getAllKB_ACs, async () => {
            const response = await this.api.post(`/knowledge-bases/`);
            return response.data;
        })();

        return this.dispatch(thunk);
    }

    public patchKB(options: IPatchKnowledgeBaseRequest) {
        const thunk = bindThunkAction(KnowledgeBaseActions.getAllKB_ACs, async () => {
            const response = await this.api.patch(`/knowledge-bases/`);
            return response.data;
        })();

        return this.dispatch(thunk);
    }
}

export function useKnowledgeBaseActions() {
    const dispatch = useDispatch();
    const actions = useMemo(() => new KnowledgeBaseActions(dispatch, apiv2), [dispatch]);
    return actions;
}
