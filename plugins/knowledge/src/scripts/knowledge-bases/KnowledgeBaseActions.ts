/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import ReduxActions, { bindThunkAction } from "@library/redux/ReduxActions";
import { IKnowledgeAppStoreState } from "@knowledge/state/model";
import { actionCreatorFactory } from "typescript-fsa";
import {
    IKnowledgeBase,
    KnowledgeBaseStatus,
    IPostKnowledgeBaseRequest,
    IPatchKnowledgeBaseRequest,
    IKnowledgeBasesState,
    IKbFormState,
} from "@knowledge/knowledge-bases/KnowledgeBaseModel";
import { IApiError } from "@library/@types/api/core";
import { useDispatch } from "react-redux";
import apiv2 from "@library/apiv2";
import { useMemo } from "react";
import { type } from "os";
import { ISearchRequestBody } from "@knowledge/@types/api/search";

const actionCreator = actionCreatorFactory("@@knowledgeBases");
interface IKknowledgeBaseParams {
    kbID: string;
}
type IGetKnowledgeBaseRequest = {
    kbID: number;
};

type IDeleteKnowledgeBaseRequest = {
    kbID: number;
};
type IGetKnowledgeBaseResponse = IKnowledgeBase[];
type IPostKnowledgeBaseResponse = IKnowledgeBase;
type IPatchKnowledgeBaseResponse = IKnowledgeBase;
type IDeleteKnowledgeBaseResponse = undefined;

/**
 * Actions for working with resources from the /api/v2/knowledge-bases endpoint.
 */
export default class KnowledgeBaseActions extends ReduxActions<IKnowledgeAppStoreState> {
    public static readonly GET_ACS = actionCreator.async<{ status: KnowledgeBaseStatus }, IKnowledgeBase[], IApiError>(
        "GET_ALL",
    );

    public getAll = (status: KnowledgeBaseStatus = KnowledgeBaseStatus.PUBLISHED) => {
        const thunk = bindThunkAction(KnowledgeBaseActions.GET_ACS, async () => {
            const response = await this.api.get(`/knowledge-bases?expand=all&status=${status}`);
            return response.data;
        })({ status });
        return this.dispatch(thunk);
    };

    public static initFormAC = actionCreator<{ kbID?: number }>("INIT_FORM");
    public initForm = this.bindDispatch(KnowledgeBaseActions.initFormAC);

    public static updateFormAC = actionCreator<Partial<IKbFormState>>("UPDATE_FORM");
    public updateForm = this.bindDispatch(KnowledgeBaseActions.updateFormAC);

    public static clearErrorAC = actionCreator("CLEAR_ERROR");
    public clearError = this.bindDispatch(KnowledgeBaseActions.clearErrorAC);

    public static getKB_ACs = actionCreator.async<IGetKnowledgeBaseRequest, IGetKnowledgeBaseResponse, IApiError>(
        "GET",
    );
    public static postKB_ACs = actionCreator.async<
        IPostKnowledgeBaseRequest,
        IPostKnowledgeBaseResponse & IKknowledgeBaseParams,
        IApiError
    >("POST");
    public static patchKB_ACs = actionCreator.async<IPatchKnowledgeBaseRequest, IPatchKnowledgeBaseResponse, IApiError>(
        "PATCH",
    );
    public static deleteKB_ACs = actionCreator.async<
        IDeleteKnowledgeBaseRequest,
        IDeleteKnowledgeBaseResponse,
        IApiError
    >("DELETE");

    public getKB(options: IGetKnowledgeBaseRequest) {
        const thunk = bindThunkAction(KnowledgeBaseActions.getKB_ACs, async () => {
            const { kbID, ...rest } = options;
            const params = { ...rest, expand: "all" };
            const response = await this.api.get(`/knowledge-bases/${options.kbID}`, { params });
            return response.data;
        })(options);

        return this.dispatch(thunk);
    }
    public saveKbForm = async () => {
        const { form } = this.getState().knowledge.knowledgeBases;

        if (form.knowledgeBaseID != null) {
            return await this.patchKB(form as any);
        } else {
            return await this.postKB(form as any);
        }
    };

    public postKB(options: IPostKnowledgeBaseRequest) {
        const thunk = bindThunkAction(KnowledgeBaseActions.postKB_ACs, async () => {
            const response = await this.api.post(`/knowledge-bases/`, options);
            return response.data;
        })(options);

        return this.dispatch(thunk);
    }

    public patchKB(options: IPatchKnowledgeBaseRequest) {
        const { knowledgeBaseID, ...body } = options;

        const thunk = bindThunkAction(KnowledgeBaseActions.patchKB_ACs, async () => {
            const response = await this.api.patch(`/knowledge-bases/${knowledgeBaseID}`, body);
            return response.data;
        })(options);

        return this.dispatch(thunk);
    }
    public deleteKB = (options: IDeleteKnowledgeBaseRequest) => {
        const apiThunk = bindThunkAction(KnowledgeBaseActions.deleteKB_ACs, async () => {
            const response = await this.api.delete(`/products/${options.kbID}`);
            return response.data;
        })(options);
        return this.dispatch(apiThunk);
    };
}

export function useKnowledgeBaseActions() {
    const dispatch = useDispatch();
    const actions = useMemo(() => new KnowledgeBaseActions(dispatch, apiv2), [dispatch]);
    return actions;
}
