/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import ReduxActions, { bindThunkAction } from "@library/redux/ReduxActions";
import { actionCreatorFactory } from "typescript-fsa";
import { IThemeForm, IThemeEditorStoreState } from "./themeEditorReducer";
import { IApiError } from "@library/@types/api/core";
import { useDispatch } from "react-redux";
import apiv2 from "@library/apiv2";
import { useMemo } from "react";
import { History } from "history";
import qs from "qs";
import { t } from "@vanilla/i18n/src";
import { ITheme, IThemeAssets } from "@vanilla/library/src/scripts/theming/themeReducer";
import ThemeActions from "@vanilla/library/src/scripts/theming/ThemeActions";
import { DeepPartial } from "redux";
const actionCreator = actionCreatorFactory("@@themeEditor");

interface IGetThemeParams {
    themeID: string | number;
    revisionID?: number | null;
}
type IGetThemeResponse = ITheme;
type IPostThemeResponse = ITheme;
export type IPostPatchThemeAssets = Partial<IThemeAssets>;

export interface IPostThemeRequest {
    name: string;
    parentTheme?: number | string;
    parentVersion?: string;
    assets?: IPostPatchThemeAssets;
}

export enum PageType {
    NEW_THEME = "newTheme",
    COPY = "copy",
    EDIT_THEME = "edit",
}

/**
 * Actions for working with resources from the /api/v2/theme endpoint.
 */

export default class ThemeEditorActions extends ReduxActions<IThemeEditorStoreState> {
    public static getTheme_ACs = actionCreator.async<IGetThemeParams, IGetThemeResponse, IApiError>("GET_THEME");
    public static postTheme_ACs = actionCreator.async<IPostThemeRequest, IPostThemeResponse, IApiError>("POST_THEME"); //Copy

    public static initAssetsAC = actionCreator<{ themeID?: string | number }>("INIT_ASSETS");
    public initAssets = this.bindDispatch(ThemeEditorActions.initAssetsAC);

    public static updateAssetsAC = actionCreator<DeepPartial<IThemeForm>>("UPDATE_ASSETS");
    public updateAssets = this.bindDispatch(ThemeEditorActions.updateAssetsAC);

    public static clearSubmitAC = actionCreator("CLEAR_SUBMIT");
    public clearSubmit = this.bindDispatch(ThemeEditorActions.clearSubmitAC);

    private themeActions = new ThemeActions(this.dispatch, this.api, this.getState);

    public getThemeById = async (themeID: number | string, history: History, revisionID: number | null = null) => {
        const query = qs.parse(history.location.search.replace(/^\?/, ""));

        let currentPageType = "";

        if (history.location.pathname === "/theme/theme-settings/add" && !query.templateName) {
            currentPageType = PageType.NEW_THEME;
        } else if (query.templateName) {
            currentPageType = PageType.COPY;
        } else {
            currentPageType = PageType.EDIT_THEME;
        }

        let request = {
            themeID: themeID,
            revisionID: revisionID,
        };

        return await this.getTheme(request, currentPageType);
    };

    public getTheme = async (options: IGetThemeParams, currentPageType: string) => {
        const thunk = bindThunkAction(ThemeEditorActions.getTheme_ACs, async () => {
            const { themeID, revisionID } = options;
            const params = revisionID
                ? { allowAddonVariables: false, revisionID: revisionID, expand: true }
                : { allowAddonVariables: false, expand: true };
            const response = await this.api.get(`/themes/${themeID}`, {
                params: params,
            });

            response.data.pageType = currentPageType;

            switch (currentPageType) {
                case PageType.NEW_THEME:
                    response.data.name = t("Untitled");
                    break;
                case PageType.COPY:
                    let themeName = t("ThemeEditor.Copy", "<0/> copy");
                    themeName = themeName.replace("<0/>", `${response.data.name}`);
                    response.data.name = themeName;
                    break;
            }

            return response.data;
        })(options);
        const response = this.dispatch(thunk);
        this.updateAssets(response.assets);
        return response;
    };

    public saveTheme = async () => {
        const { form } = this.getState().themeEditor;
        const { themeID, pageType } = this.getState().themeEditor.form;

        const request = {
            name: form.name,
            assets: form.assets,
        };

        let result: any;
        if (form.type == "themeDB" && pageType === PageType.EDIT_THEME) {
            if (themeID) {
                result = await this.themeActions.patchTheme({
                    ...request,
                    themeID,
                });
            }
        } else {
            result = await this.postTheme({
                ...request,
                parentTheme: form.type === "themeDB" ? form.parentTheme : form.themeID,
                parentVersion: form.version,
            });
        }
        this.updateAssets({ edited: false });
        // Try to clear our submit status after some time.
        setTimeout(() => {
            this.clearSubmit();
        }, 10000);

        return result;
    };

    public postTheme(options: IPostThemeRequest) {
        const thunk = bindThunkAction(ThemeEditorActions.postTheme_ACs, async () => {
            const response = await this.api.post(`/themes`, options);
            return response.data;
        })(options);

        return this.dispatch(thunk);
    }
}

export function useThemeEditorActions() {
    const dispatch = useDispatch();
    const actions = useMemo(() => new ThemeEditorActions(dispatch, apiv2), [dispatch]);
    return actions;
}
