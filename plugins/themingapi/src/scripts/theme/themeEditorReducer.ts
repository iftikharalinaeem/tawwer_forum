/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { reducerWithInitialState } from "typescript-fsa-reducers";
import produce from "immer";
import { LoadStatus, ILoadable } from "@library/@types/api/core";
import { useSelector } from "react-redux";
import ThemeActions, { useThemeActions } from "./ThemeEditorActions";
import { ICoreStoreState } from "@vanilla/library/src/scripts/redux/reducerRegistry";

export interface IThemeAssets {
    fonts?: { data: IThemeFont[] };
    logo?: IThemeExternalAsset;
    mobileLogo?: IThemeExternalAsset;
    variables?: IThemeVariables;
    header?: IThemeHeader;
    footer?: IThemeFooter;
    javascript?: string;
    styles?: string;
}

export interface IPostPatchThemeAssets {
    fonts?: { data: IThemeFont[] };
    logo?: IThemeExternalAsset;
    mobileLogo?: IThemeExternalAsset;
    variables?: IThemeVariables;
    header?: IThemeHeader;
    footer?: IThemeFooter;
    javascript?: string;
    styles?: string;
}

export interface IThemeHeader {
    data?: string;
    type: string;
}
export interface IThemeFooter {
    data?: string;
    type: string;
}
export interface IThemeFont {
    name: string;
    url: string;
    fallbacks: string[];
}

export interface IThemeExternalAsset {
    type: string;
    url: string;
}

export interface IThemeVariables {
    [key: string]: string;
}

export interface ITheme {
    themeID: string | number;
    name: string;
    type: string;
    assets: IThemeAssets;
    parentTheme: string;
    version: string;
}

export interface IThemeForm {
    themeID?: number | string;
    name: string;
    type: string;
    assets: IThemeAssets;
    parentTheme: string;
    version: string;
}

export interface IThemeState {
    theme: ILoadable<ITheme>;
    form: IThemeForm;
    formSubmit: ILoadable<{}>;
}
export const INITIAL_ASSETS: IThemeAssets = {
    header: {
        data: "",
        type: "html",
    },
    footer: {
        data: "",
        type: "html",
    },
    javascript: "",
    styles: "",
    fonts: {
        data: [],
    },
    logo: {
        type: "",
        url: "",
    },
    mobileLogo: {
        type: "",
        url: "",
    },
    variables: { key: "" },
};
const INITIAL_STATE: IThemeState = {
    theme: {
        status: LoadStatus.PENDING,
    },
    form: {
        name: "",
        type: "themeDB",
        assets: INITIAL_ASSETS,
        parentTheme: "",
        version: "",
    },
    formSubmit: {
        status: LoadStatus.PENDING,
    },
};

export const themeEditorReducer = produce(
    reducerWithInitialState<IThemeState>(INITIAL_STATE)
        .case(ThemeActions.getTheme_ACs.started, (state, payload) => {
            state.theme.status = LoadStatus.LOADING;
            return state;
        })
        .case(ThemeActions.getTheme_ACs.failed, (state, payload) => {
            state.theme.status = LoadStatus.ERROR;
            state.theme.error = payload.error;
            return state;
        })
        .case(ThemeActions.getTheme_ACs.done, (state, payload) => {
            state.theme.status = LoadStatus.SUCCESS;
            state.theme.data = payload.result;
            state.form = payload.result;
            return state;
        })
        .case(ThemeActions.updateAssetsAC, (state, payload) => {
            state.form = {
                ...state.form,
                ...payload,
                assets: {
                    ...state.form.assets,
                    ...(payload.assets ?? payload.assets),
                },
            };

            return state;
        })
        .case(ThemeActions.postTheme_ACs.started, (state, payload) => {
            state.formSubmit.status = LoadStatus.LOADING;
            return state;
        })
        .case(ThemeActions.postTheme_ACs.failed, (state, payload) => {
            state.formSubmit.status = LoadStatus.ERROR;
            state.formSubmit.error = payload.error;
            return state;
        })
        .case(ThemeActions.postTheme_ACs.done, (state, payload) => {
            state.formSubmit.status = LoadStatus.SUCCESS;
            state.formSubmit.data = payload.result;
            return state;
        })
        .case(ThemeActions.patchTheme_ACs.started, (state, payload) => {
            state.formSubmit.status = LoadStatus.LOADING;
            return state;
        })
        .case(ThemeActions.patchTheme_ACs.failed, (state, payload) => {
            state.formSubmit.status = LoadStatus.ERROR;
            state.formSubmit.error = payload.error;

            return state;
        })
        .case(ThemeActions.patchTheme_ACs.done, (state, payload) => {
            state.formSubmit.status = LoadStatus.SUCCESS;
            state.formSubmit.data = payload.result;
            return state;
        }),
);

export interface IThemeEditorStoreState extends ICoreStoreState {
    themeEditor: IThemeState;
}

export function useThemeEditorState() {
    return useSelector((state: IThemeEditorStoreState) => state.themeEditor);
}
