/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import ReduxActions, { bindThunkAction } from "@library/redux/ReduxActions";
import {} from "@knowledge/state/model";
import { actionCreatorFactory } from "typescript-fsa";
import { IPostThemeRequest, IPatchThemeRequest, ITheme, IThemeState } from "./ThemeReducers";
import { IApiError } from "@library/@types/api/core";
import { useDispatch } from "react-redux";
import apiv2 from "@library/apiv2";
import { useMemo } from "react";

const actionCreator = actionCreatorFactory("@@knowledgeBases");
interface IGetThemeParams {
    themeID: string;
}
type IGetThemeResponse = IThemeState;
type IPostThemeResponse = IThemeState;
type IPatchThemeResponse = IThemeState;
interface IGetAllThemesReauest {}

/**
 * Actions for working with resources from the /api/v2/knowledge-bases endpoint.
 */

export default class ThemeActions extends ReduxActions {
    [themeID: string]: any;
    public static readonly getAllThemes_ACS = actionCreator.async<{}, IThemeState, IApiError>("GET_ALL_THEMES");
    public static getThemeAssets_ACs = actionCreator.async<IGetThemeParams, IGetThemeResponse, IApiError>("GET");
    public static postTheme_ACs = actionCreator.async<IPostThemeRequest, IPostThemeResponse, IApiError>("POST"); //Copy
    public static patchTheme_ACs = actionCreator.async<IPatchThemeRequest, IPatchThemeResponse, IApiError>("PATCH");

    public static initAssetsAC = actionCreator<{ themeID?: number }>("INIT_ASSETS");
    public initForm = this.bindDispatch(ThemeActions.initAssetsAC);
    public static updateAssetsAC = actionCreator<Partial<IThemeState>>("UPDATE_ASSETS");
    public updateForm = this.bindDispatch(ThemeActions.updateAssetsAC);

    public getAllThemes = () => {
        const thunk = bindThunkAction(ThemeActions.getAllThemes_ACS, async () => {
            const params = { expand: "all" };
            const response = await this.api.get(`/themes/`, { params });

            return response.data;
        })();
        return this.dispatch(thunk);
    };
    public getThemeAssets(options: IGetThemeParams) {
        const thunk = bindThunkAction(ThemeActions.getThemeAssets_ACs, async () => {
            const { themeID } = options;
            const response = await this.api.get(`/themes/foundation`);
            return response.data;
        })(options);

        return this.dispatch(thunk);
    }
    public saveTheme = async () => {
        /* const data: IThemeState = {};

        if (data.assets.data?.header === undefined) {
            data.assets.data?.header = "";
        }

        if (data.assets.data?.header === null) {
            data.assets.data?.footer = "";
        }

        if (data.themeID != null) {
            return await this.patchTheme(data as any);
        } else {
            return await this.postTheme(data as any);
        }*/
    };

    public postTheme(options: IPostThemeRequest) {
        const thunk = bindThunkAction(ThemeActions.postTheme_ACs, async () => {
            const response = await this.api.post(`/themes/`, options);
            return response.data;
        })(options);

        return this.dispatch(thunk);
    }

    public patchTheme(options: IPatchThemeRequest) {
        const { themeID, ...body } = options;

        const thunk = bindThunkAction(ThemeActions.patchTheme_ACs, async () => {
            const response = await this.api.patch(`/themse/${themeID}`, body);
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
