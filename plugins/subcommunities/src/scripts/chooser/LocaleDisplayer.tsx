/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */
import React from "react";
import { useAvailableLocales } from "@subcommunities/subcommunities/subcommunitySelectors";
import { getMeta, t } from "@library/utility/appUtils";

interface IProps {
    localeContent: string; // The locale key to translate into a full name.
    displayLocale?: string; // The language to use for the display.
}

export function LocaleDisplayer(props: IProps) {
    const locales = useAvailableLocales();
    if (!locales) {
        return null;
    }

    const currentSiteLocale = getMeta("ui.localeKey");
    let fullLocaleName = locales[props.localeContent].translatedNames[props.displayLocale || currentSiteLocale];

    if (!fullLocaleName) {
        fullLocaleName = t(props.localeContent);
    }

    fullLocaleName = fullLocaleName.charAt(0).toLocaleUpperCase() + fullLocaleName.slice(1);

    return <span lang={props.displayLocale}>{fullLocaleName}</span>;
}
