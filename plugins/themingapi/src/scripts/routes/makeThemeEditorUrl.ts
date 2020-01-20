/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import getStore from "@library/redux/getStore";
import { logWarning } from "@vanilla/utils";
import qs from "qs";
import { Store } from "redux";

export interface IThemeEditorURLData {
    themeID?: string | number;
    action: string;
}

/**
 * Get the route for editing a particular article ID.
 *
 * @param themeID - The articleID.
 */
export function makeThemeEditorUrl(data?: IThemeEditorURLData, store?: Store<IThemeEditorURLData>) {
    const defaultAddRoot = "/theme/theme-settings/add";
    if (!data) {
        return defaultAddRoot;
    }
    let baseUrl = "";
    const baseTheme = "foundation";

    store = store ?? getStore<IThemeEditorURLData>();

    if (data.action === "edit" && data.themeID !== null) {
        baseUrl = `/theme/theme-settings/${data.themeID}/edit`;
    } else if (data.action === "copy") {
        baseUrl = `/theme/add?templateName=${data.themeID}`;
    } else {
        baseUrl = defaultAddRoot;
    }

    return baseUrl;
}
