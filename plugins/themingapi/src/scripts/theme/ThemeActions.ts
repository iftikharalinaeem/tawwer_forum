/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import ReduxActions, { bindThunkAction } from "@library/redux/ReduxActions";
import {} from "@knowledge/state/model";
import { actionCreatorFactory } from "typescript-fsa";
import { IThemeState, IThemeAssets, ITheme } from "./themeEditorReducer";
import { IApiError } from "@library/@types/api/core";
import { useDispatch } from "react-redux";
import apiv2 from "@library/apiv2";
import { useMemo } from "react";

const actionCreator = actionCreatorFactory("@@knowledgeBases");
interface IGetThemeParams {
    themeID: string;
}
type IGetThemeResponse = ITheme;
type IPostThemeResponse = ITheme;
type IPatchThemeResponse = ITheme;
interface IGetAllThemesReauest {}

const HARDCODED_THEME_ID = 1;

export interface IPostThemeRequest {
    name: string;
    parentTheme?: string;
    parentVersion?: string;
    assets?: IThemeAssets;
}

export interface IPatchThemeRequest {
    themeID: number;
    name: string;
    parentTheme?: string;
    parentVersion?: string;
    assets?: Partial<IThemeAssets>;
}

/**
 * Actions for working with resources from the /api/v2/knowledge-bases endpoint.
 */

export default class ThemeActions extends ReduxActions {
    [themeID: string]: any;
    public static readonly getAllThemes_ACS = actionCreator.async<{}, IThemeState, IApiError>("GET_ALL_THEMES");

    public static getTheme_ACs = actionCreator.async<IGetThemeParams, IGetThemeResponse, IApiError>("GET_THEME");
    public static postTheme_ACs = actionCreator.async<IPostThemeRequest, IPostThemeResponse, IApiError>("POST_THEME"); //Copy

    public static patchTheme_ACs = actionCreator.async<IPatchThemeRequest, IPatchThemeResponse, IApiError>(
        "PATCH_THEME",
    );

    public static initAssetsAC = actionCreator<{ themeID?: number }>("INIT_ASSETS");
    public initAssets = this.bindDispatch(ThemeActions.initAssetsAC);

    public static updateAssetsAC = actionCreator<Partial<IThemeAssets>>("UPDATE_ASSETS");
    public updateAssets = this.bindDispatch(ThemeActions.updateAssetsAC);

    public getTheme = async (options: IGetThemeParams) => {
        const thunk = bindThunkAction(ThemeActions.getTheme_ACs, async () => {
            const { themeID } = options;
            const response = await this.api.get(`/themes/${options}`);

            return response.data;
        })(options);
        const response = await this.dispatch(thunk);
        // Update the form the response data.
        this.updateAssets(response.assets);
    };

    public saveTheme = async (assets: Partial<IThemeAssets>, themeID?: number) => {
        const request = {
            name: "Custom theme name",
            assets: assets,
            // assets: this.getState().theme.assets.data,
        };

        if (themeID != null) {
            return await this.patchTheme({
                ...request,
                themeID,
            });
        } else {
            return await this.postTheme(request);
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
            const response = await this.api.patch(`/themes/${HARDCODED_THEME_ID}`, body);
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
