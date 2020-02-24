/**
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import qs from "qs";
import { Store } from "redux";

export interface IThemeEditorURLData {
    themeID?: string | number;
    templateName?: string;
    newTheme?: boolean;
}

/**
 * Get the route for editing a particular article ID.
 *
 * @param themeID - The articleID.
 */
export function makeThemeEditorUrl(data?: IThemeEditorURLData, store?: Store<IThemeEditorURLData>) {
    const defaultAddRoot = "/theme/theme-settings/add";
    if (!data || data.newTheme) {
        return defaultAddRoot;
    }

    let baseUrl = data.themeID ? `/theme/theme-settings/${data.themeID}/edit` : defaultAddRoot;
    const { templateName } = data;

    const query = qs.stringify({
        templateName,
    });
    if (query) {
        baseUrl += `?${query}`;
    }
    return baseUrl;
}
export function makeThemeEditorUrl1(data?: IThemeEditorURLData, store?: Store<IThemeEditorURLData>) {
    return "/theme/theme-settings/preview";
}
