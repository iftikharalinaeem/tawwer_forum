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
    preview?: true;
}

/**
 * Get the route for editing a particular article ID.
 *
 * @param themeID - The articleID.
 */
export function makeThemeEditorUrl(data?: IThemeEditorURLData, store?: Store<IThemeEditorURLData>) {
    const defaultAddRoot = "/theme/theme-settings/add";
    const defaultPreviewRoot = "/theme/theme-settings/preview";
    if (!data || data.newTheme) {
        return defaultAddRoot;
    }

    if (data.preview) {
        return "/theme/theme-settings/preview";
    }

    let baseUrl = data.themeID
        ? `/theme/theme-settings/${data.themeID}/edit`
        : data.preview
        ? defaultPreviewRoot
        : defaultAddRoot;
    const { templateName } = data;

    const query = qs.stringify({
        templateName,
    });
    if (query) {
        baseUrl += `?${query}`;
    }

    return baseUrl;
}
