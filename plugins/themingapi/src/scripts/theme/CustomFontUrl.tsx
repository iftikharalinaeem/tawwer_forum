/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { isAllowedUrl } from "@library/utility/appUtils";
import { t } from "@vanilla/i18n/src";
import { ThemeInputText } from "@library/forms/themeEditor/ThemeInputText";

interface IProps {}

function urlValidation(url: any) {
    return url ? isAllowedUrl(url.toString()) : false;
}

export function CustomFontUrl(props: IProps) {
    return (
        <ThemeInputText
            varKey={"global.fonts.customFont.url"}
            debounceTime={10}
            validation={newValue => {
                return newValue !== "" || urlValidation(newValue);
            }}
            errorMessage={t("Invalid URL")}
        />
    );
}
