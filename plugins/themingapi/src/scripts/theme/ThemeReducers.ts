/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import ReduxReducer from "@library/redux/ReduxReducer";
import { IKnowledgeAppStoreState } from "@knowledge/state/model";
import { reducerWithInitialState } from "typescript-fsa-reducers";
import produce from "immer";
import { LoadStatus, ILoadable } from "@library/@types/api/core";
import { useSelector } from "react-redux";
import ThemeActions from "./ThemeActions";
import { IThemeState } from "@library/theming/themeReducer";

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

export interface IThemePageState {
    form: IThemeAssets;
    results: ILoadable<IThemeAssets>;
}
export const INITIAL_STATE: IThemeState = {
    assets: {
        status: LoadStatus.PENDING,
    },
};
/**
 * Model for working with actions & data related to the /api/v2/knowledge-bases endpoint.
 */

export const ThemePageReducer = produce(
    reducerWithInitialState<IThemeState>(INITIAL_STATE)
        .case(ThemeActions.getAllThemes_ACS.started, state => {
            state.assets.status = LoadStatus.LOADING;
            return state;
        })
        .case(ThemeActions.getAllThemes_ACS.done, (state, payload) => {
            state.assets.status = LoadStatus.SUCCESS;
            state.assets.data = payload.result;
            console.log("sss=>", state);
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
        }),
    /*
        .case(ThemeEditorActions.updateFormAC, (nextState, payload) => {
            nextState.form = {
                ...nextState.form,
                ...payload,
            };
            return nextState;
        })
        .case(ThemeEditorActions.getSearchACs.started, (nextState, payload) => {
            nextState.results.status = LoadStatus.LOADING;
            if (payload.page != null) {
                nextState.form.page = payload.page;
            }
            return nextState;
        })
        .case(ThemeEditorActions.getSearchACs.done, (nextState, payload) => {
            nextState.results.status = LoadStatus.SUCCESS;
            nextState.results.data = payload.result.body;
            nextState.pages = payload.result.pagination;

            return nextState;
        })
        .case(ThemeEditorActions.getSearchACs.failed, (nextState, payload) => {
            nextState.results.status = LoadStatus.ERROR;
            nextState.results.error = payload.error;

            return nextState;
        })
        .case(ThemeEditorActions.resetAC, () => {
            return INITIAL_STATE;
        }),*/
);

export function useThemePageData() {
    return useSelector((state: IKnowledgeAppStoreState) => state.Theme);
}
