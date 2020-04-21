/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { ILoadable, LoadStatus } from "@library/@types/api/core";
import { ICoreStoreState } from "@vanilla/library/src/scripts/redux/reducerRegistry";
import produce from "immer";
import { useSelector } from "react-redux";
import { reducerWithInitialState } from "typescript-fsa-reducers";
import ThemeActions, { PageType } from "./ThemeEditorActions";
import { ITheme, IThemeAssets, ThemeType } from "@vanilla/library/src/scripts/theming/themeReducer";
import clone from "lodash/clone";

export interface IThemeExternalAsset {
    type: string;
    url: string;
}

export interface IThemeForm extends Omit<ITheme, "themeID" | "features" | "preview"> {
    themeID?: number | string;
    pageType: PageType;
    errors: boolean;
    initialLoad: boolean;
    edited: boolean;
}

export interface IThemeState {
    theme: ILoadable<ITheme>;
    form: IThemeForm;
    formSubmit: ILoadable<ITheme>;
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
    variables: { data: "", type: "" },
};
const INITIAL_STATE: IThemeState = {
    theme: {
        status: LoadStatus.PENDING,
    },
    form: {
        current: false,
        name: "",
        type: ThemeType.DB,
        assets: INITIAL_ASSETS,
        parentTheme: "",
        version: "",
        pageType: PageType.NEW_THEME,
        errors: false,
        initialLoad: true,
        edited: false,
    },
    formSubmit: {
        status: LoadStatus.PENDING,
    },
};

export const themeEditorReducer = produce(
    reducerWithInitialState<IThemeState>(clone(INITIAL_STATE))
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
            state.form = {
                ...state.form,
                ...payload.result,
            };
            return state;
        })
        .case(ThemeActions.updateAssetsAC, (state, payload) => {
            if (payload !== undefined) {
                state.form = {
                    ...state.form,
                    ...payload,
                    assets: {
                        ...state.form.assets,
                        ...payload.assets,
                    },
                    initialLoad: false,
                    edited: payload.edited ?? true,
                };
            } else {
                state.form.assets = INITIAL_ASSETS;
            }

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
            state.form = {
                ...state.form,
                parentTheme: payload.result.parentTheme,
                version: payload.result.version,
                themeID: payload.result.themeID,
                type: ThemeType.DB,
                pageType: PageType.EDIT_THEME,
                errors: false,
            };
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
        })
        .case(ThemeActions.clearSubmitAC, state => {
            if (state.formSubmit.status !== LoadStatus.LOADING) {
                // Do not modify anything if we currently have a request in-flight.
                state.formSubmit = INITIAL_STATE.formSubmit;
            }
            return state;
        }),
);

export interface IThemeEditorStoreState extends ICoreStoreState {
    themeEditor: IThemeState;
}

export function useThemeEditorState() {
    return useSelector((state: IThemeEditorStoreState) => state.themeEditor);
}
