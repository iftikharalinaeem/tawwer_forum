/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import ReduxReducer from "@library/redux/ReduxReducer";
import { reducerWithInitialState, reducerWithoutInitialState } from "typescript-fsa-reducers";
import produce from "immer";
import clone from "lodash/clone";
import { LoadStatus, ILoadable } from "@library/@types/api/core";
import { useSelector } from "react-redux";
import ThemeActions from "./ThemeActions";

export interface IThemeAssets {
    header: string;
    footer: string;
}

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
    assets?: IThemeAssets;
}
export interface ITheme {}

export const INITIAL_STATE: IThemeState = {
    assets: {
        status: LoadStatus.PENDING,
    },
    themeID: "",
    type: "",
};
export const INITIAL_ASSETS = {
    header: "",
    footer: "",
    javascript: "",
    style: "",
};
export interface IThemeState {
    themeID: string;
    type: string;
    assets: ILoadable<IThemeAssets>;
    formSubmit: ILoadable<{}>;
}

export default class ThemePageReducer implements ReduxReducer<IThemeState> {
    public static INITIAL_STATE: IThemeState = {
        themeID: "",
        assets: INITIAL_ASSETS,
        type: "",
    };

    public initialState = ThemePageReducer.INITIAL_STATE;
    public reducer: ReducerType = (state = clone(this.initialState), action) => {
        return produce(state, nextState => {
            return this.internalReducer(nextState, action);
        });
    };
    private internalReducer = reducerWithoutInitialState<IThemeState>()
        .case(ThemeActions.initAssetsAC, (state, payload) => {
            if (payload.themeID != null) {
                const existingAsset = {
                    ...state.assets, //themeID.data[payload.themeID],
                };
                state.assets = existingAsset;
            } else {
                console.log("restoring to initial");
                state.assets = INITIAL_ASSETS;
            }

            return state;
        })
        .case(ThemeActions.getAllThemes_ACS.started, state => {
            state.assets.status = LoadStatus.LOADING;
            return state;
        })
        .case(ThemeActions.getAllThemes_ACS.done, (state, payload) => {
            state.assets.status = LoadStatus.SUCCESS;
            state.assets = payload.result;

            return state;
        })
        .case(ThemeActions.getAllThemes_ACS.failed, (state, payload) => {
            if (payload.error.response && payload.error.response.status === 404) {
                // This theme just doesn't have variables. Use the defaults.
                state.assets.data = {};
                state.assets.status = LoadStatus.SUCCESS;
                return state;
            } else {
                state.assets.status = LoadStatus.ERROR;
                state.assets.error = payload.error;
                return state;
            }
        })
        .case(ThemeActions.updateAssetsAC, (state, payload) => {
            state.assets = {
                ...state.assets,
                ...payload,
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
            state.formSubmit.data![payload.result.themeID] = payload.result;
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
            state.formSubmit.data![payload.result.themeID] = payload.result;

            return state;
        });
}

type ReducerType = IThemeState;

export function useThemeData() {
    return useSelector((state: IThemeState) => state.assets);
}
