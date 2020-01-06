/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import ReduxActions, { bindThunkAction } from "@library/redux/ReduxActions";
import { IKnowledgeAppStoreState } from "@knowledge/state/model";
import { actionCreatorFactory } from "typescript-fsa";
import { IPostThemeRequest, IPatchThemeRequest, ITheme } from "./ThemeReducers";
import { IApiError } from "@library/@types/api/core";
import { useDispatch } from "react-redux";
import apiv2 from "@library/apiv2";
import { useMemo } from "react";

const actionCreator = actionCreatorFactory("@@knowledgeBases");
interface IGetThemeRequest {
    themekey: string;
}
type IGetThemeResponse = ITheme;
type IPostThemeResponse = ITheme;
type IPatchThemeResponse = ITheme;
interface IGetAllThemesReauest {}

/**
 * Actions for working with resources from the /api/v2/knowledge-bases endpoint.
 */
export default class ThemeActions extends ReduxActions<IKnowledgeAppStoreState> {
    [x: string]: any;
    public static readonly getAllThemes_ACS = actionCreator.async<{}, ITheme, IApiError>("GET_ALL_Themes");
    public static getThemeAssets_ACs = actionCreator.async<IGetThemeRequest, IGetThemeResponse, IApiError>("GET");
    public static postTheme_ACs = actionCreator.async<IPostThemeRequest, IPostThemeResponse, IApiError>("POST"); //Copy
    public static patchTheme_ACs = actionCreator.async<IPatchThemeRequest, IPatchThemeResponse, IApiError>("PATCH");

    public getAllThemes = () => {
        const thunk = bindThunkAction(ThemeActions.getAllThemes_ACS, async () => {
            const response = await this.api.get(`/themes/`);
            console.log("all->", response.data);
            return response.data;
        })();
        return this.dispatch(thunk);
    };
    public getThemeAssets(options: IGetThemeRequest) {
        const thunk = bindThunkAction(ThemeActions.getThemeAssets_ACs, async () => {
            const { themekey } = options;
            const response = await this.api.get(`/themes/${options.themekey}`);
            return response.data;
        })(options);

        return this.dispatch(thunk);
    }
    public saveTheme = async () => {
        const { data } = this.getState().Theme.assets;

        // Kludge our image types to be empty strings if null.
        /*if (data?.header === undefined) {
            data?.header = "";
        }

        if (data?.footer === null) {
            data?.footer = "";
        }

        if (form.themekey != null) {
            return await this.patchTheme(form as any);
        } else {
            return await this.patchTheme(form as any);
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
