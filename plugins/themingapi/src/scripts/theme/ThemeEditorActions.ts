/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import ReduxActions, { bindThunkAction } from "@library/redux/ReduxActions";
import { actionCreatorFactory } from "typescript-fsa";
import { IThemeAssets, ITheme, IPostPatchThemeAssets, IThemeForm, IThemeEditorStoreState } from "./themeEditorReducer";
import { IApiError } from "@library/@types/api/core";
import { useDispatch } from "react-redux";
import apiv2 from "@library/apiv2";
import { useMemo } from "react";

const actionCreator = actionCreatorFactory("@@themeEditor");

interface IGetThemeParams {
    themeID: string | number;
}
type IGetThemeResponse = ITheme;
type IPostThemeResponse = ITheme;
type IPatchThemeResponse = ITheme;

export interface IPostThemeRequest {
    name: string;
    parentTheme?: string;
    parentVersion?: string;
    assets?: IPostPatchThemeAssets;
}
export interface IPatchThemeRequest {
    themeID: string | number;
    name: string;
    parentTheme?: string;
    parentVersion?: string;
    assets?: Partial<IPostPatchThemeAssets>;
}

/**
 * Actions for working with resources from the /api/v2/theme endpoint.
 */

export default class ThemeActions extends ReduxActions<IThemeEditorStoreState> {
    public static getTheme_ACs = actionCreator.async<IGetThemeParams, IGetThemeResponse, IApiError>("GET_THEME");
    public static postTheme_ACs = actionCreator.async<IPostThemeRequest, IPostThemeResponse, IApiError>("POST_THEME"); //Copy

    public static patchTheme_ACs = actionCreator.async<IPatchThemeRequest, IPatchThemeResponse, IApiError>(
        "PATCH_THEME",
    );

    public static initAssetsAC = actionCreator<{ themeID?: string | number }>("INIT_ASSETS");
    public initAssets = this.bindDispatch(ThemeActions.initAssetsAC);

    public static updateAssetsAC = actionCreator<Partial<IThemeForm>>("UPDATE_ASSETS");
    public updateAssets = this.bindDispatch(ThemeActions.updateAssetsAC);

    public static updateHeaderAssetsAC = actionCreator<Partial<IThemeForm>>("UPDATE_Header_ASSETS");
    public updateHeaderAssets = this.bindDispatch(ThemeActions.updateHeaderAssetsAC);

    public static updateFooterAssetsAC = actionCreator<Partial<IThemeForm>>("UPDATE_Footer_ASSETS");
    public updateFooterAssets = this.bindDispatch(ThemeActions.updateFooterAssetsAC);

    public getThemeById = async (themeID: number | string) => {
        const request = {
            themeID: themeID,
        };
        return await this.getTheme(request);
    };

    public getTheme = async (options: IGetThemeParams) => {
        const thunk = bindThunkAction(ThemeActions.getTheme_ACs, async () => {
            const { themeID } = options;
            const response = await this.api.get(`/themes/${options.themeID}`);
            return response.data;
        })(options);
        const response = this.dispatch(thunk);
        this.updateAssets(response.assets); //Update the form the response data.
        return response;
    };

    public saveTheme = async () => {
        const { form } = this.getState().themeEditor;
        const { themeID } = this.getState().themeEditor.form;
        const assets = {
            header: form.assets.header,
            footer: form.assets.footer,
            styles: form.assets.styles,
            javascript: form.assets.javascript,
        };
        const request = {
            name: form.name,
            assets: assets,
        };

        console.log("-->", request);
        if (form.type == "themeDB") {
            if (themeID) {
                return await this.patchTheme({
                    ...request,
                    themeID,
                });
            }
        } else {
            return await this.postTheme({ ...request, parentTheme: form.parentTheme });
        }
    };

    public postTheme(options: IPostThemeRequest) {
        const thunk = bindThunkAction(ThemeActions.postTheme_ACs, async () => {
            const response = await this.api.post(`/themes`, options);
            return response.data;
        })(options);

        return this.dispatch(thunk);
    }

    public patchTheme(options: IPatchThemeRequest) {
        const { themeID, ...body } = options;

        const thunk = bindThunkAction(ThemeActions.patchTheme_ACs, async () => {
            const response = await this.api.patch(`/themes/${options.themeID}`, body);
            return response.data;
        })(options);

        return this.dispatch(thunk);
    }
}

export function useThemeActions() {
    const dispatch = useDispatch();
    const actions = useMemo(() => new ThemeActions(dispatch, apiv2), [dispatch]);
    return actions;
}
