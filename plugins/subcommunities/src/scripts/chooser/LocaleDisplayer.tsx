/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */
import React from "react";
import { useAvailableLocales, useLocaleInfo } from "@subcommunities/subcommunities/subcommunitySelectors";
import { t } from "@library/utility/appUtils";

interface IProps {
    localeContent: string; // The locale key to translate into a full name.
    displayLocale?: string; // The language to use for the display.
}

/**
 * Component for displaying a locale translated into a different locale.
 *
 * Currently this relies on the subcommuntiies endpoint to provide all translations for active locale.s
 */
export function LocaleDisplayer(props: IProps) {
    const locales = useAvailableLocales();
    const localeInfo = useLocaleInfo();
    if (!locales || !localeInfo) {
        return null;
    }

    let fullLocaleName = locales[props.localeContent].translatedNames[props.displayLocale || localeInfo.defaultLocale];

    if (!fullLocaleName) {
        fullLocaleName = t(props.localeContent);
    }

    fullLocaleName = fullLocaleName.charAt(0).toLocaleUpperCase() + fullLocaleName.slice(1);

    return <span lang={props.displayLocale}>{fullLocaleName}</span>;
}
