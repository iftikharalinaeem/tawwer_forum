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
    templateName?: string;
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
    let baseUrl = data.themeID ? `/theme/theme-settings/${data.themeID}/edit` : defaultAddRoot;
    const { templateName } = data;

    if (templateName === undefined) {
        logWarning("Attempted to initialize theme editor without template name . Please check template name.");
    }

    const query = qs.stringify({
        templateName,
    });
    if (query) {
        baseUrl += `?${query}`;
    }
    return baseUrl;
}
