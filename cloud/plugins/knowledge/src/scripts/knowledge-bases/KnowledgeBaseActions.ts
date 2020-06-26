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
    IKbFormState,
} from "@knowledge/knowledge-bases/KnowledgeBaseModel";
import { IApiError } from "@library/@types/api/core";
import { useDispatch } from "react-redux";
import apiv2 from "@library/apiv2";
import { useMemo } from "react";
import { getSiteSection } from "@library/utility/appUtils";
import { getCurrentLocale } from "@vanilla/i18n";

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
interface IGetKnowledgeBasesRequest {
    status: KnowledgeBaseStatus;
    siteSectionGroup?: string;
    locale?: string;
    sourceLocale?: string;
}
type IGetKnowledgeBaseResponse = IKnowledgeBase[];
type IPostKnowledgeBaseResponse = IKnowledgeBase;
type IPatchKnowledgeBaseResponse = IKnowledgeBase;
type IDeleteKnowledgeBaseResponse = undefined;

/**
 * Actions for working with resources from the /api/v2/knowledge-bases endpoint.
 */
export default class KnowledgeBaseActions extends ReduxActions<IKnowledgeAppStoreState> {
    public static readonly getAllACs = actionCreator.async<IGetKnowledgeBasesRequest, IKnowledgeBase[], IApiError>(
        "GET_ALL",
    );

    public getAll = (options: IGetKnowledgeBasesRequest = { status: KnowledgeBaseStatus.PUBLISHED }) => {
        options = {
            locale: getCurrentLocale(),
            siteSectionGroup: getSiteSection().sectionGroup,
            ...options,
        };

        const thunk = bindThunkAction(KnowledgeBaseActions.getAllACs, async () => {
            const response = await this.api.get(`/knowledge-bases`, {
                params: {
                    ...options,
                    expand: "all",
                },
            });
            return response.data;
        })(options);
        return this.dispatch(thunk);
    };

    public static initFormAC = actionCreator<{ kbID?: number }>("INIT_FORM");
    public initForm = this.bindDispatch(KnowledgeBaseActions.initFormAC);

    public static updateFormAC = actionCreator<Partial<IKbFormState>>("UPDATE_FORM");
    public updateForm = this.bindDispatch(KnowledgeBaseActions.updateFormAC);

    public static clearErrorAC = actionCreator("CLEAR_ERROR");
    public clearError = this.bindDispatch(KnowledgeBaseActions.clearErrorAC);

    public static clearPatchStatusAC = actionCreator<{ kbID: number }>("CLEAR_PATCH_STATUS");
    public clearPatchStatus = this.bindDispatch(KnowledgeBaseActions.clearPatchStatusAC);

    public static clearDeleteStatus = actionCreator<{ kbID: number }>("CLEAR_DELETE_STATUS");
    public clearDeleteStatus = this.bindDispatch(KnowledgeBaseActions.clearDeleteStatus);

    public static getSingleACs = actionCreator.async<IGetKnowledgeBaseRequest, IKnowledgeBase, IApiError>("GET");
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

    public getSingleKB = (options: IGetKnowledgeBaseRequest) => {
        const thunk = bindThunkAction(KnowledgeBaseActions.getSingleACs, async () => {
            const { kbID, ...rest } = options;
            const params = { ...rest, expand: "all" };
            const response = await this.api.get(`/knowledge-bases/${options.kbID}`, { params });
            return response.data;
        })(options);

        return this.dispatch(thunk);
    };
    public saveKbForm = async () => {
        const { form } = this.getState().knowledge.knowledgeBases;

        // Kludge our image types to be empty strings if null.
        if (form.bannerImage === null) {
            form.bannerImage = "";
        }

        if (form.icon === null) {
            form.icon = "";
        }

        if (!form.siteSectionGroup) {
            form.siteSectionGroup = "vanilla";
        }

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

    public patchKBStatus = (knowledgeBaseID: number, newStatus: KnowledgeBaseStatus) => {
        return this.patchKB({ knowledgeBaseID, status: newStatus });
    };

    public deleteKB = (options: IDeleteKnowledgeBaseRequest) => {
        const apiThunk = bindThunkAction(KnowledgeBaseActions.deleteKB_ACs, async () => {
            const response = await this.api.delete(`/knowledge-bases/${options.kbID}`);
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
