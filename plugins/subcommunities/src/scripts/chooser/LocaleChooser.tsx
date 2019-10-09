/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */
import DropDownItemButton from "@library/flyouts/items/DropDownItemButton";
import { RightChevronIcon } from "@library/icons/common";
import Loader from "@library/loaders/Loader";
import { subcommunityChooserClasses } from "@subcommunities/chooser/subcommunityChooserStyles";
import { useLocaleInfo, LocaleDisplayer } from "@vanilla/i18n";
import React from "react";
import { useAvailableSubcommunityLocales } from "@subcommunities/subcommunities/subcommunitySelectors";

interface IProps {
    value: string | null;
    onChange: (value: string) => void;
}

/**
 * Component for choosing a locale.
 */
export function LocaleChooser(props: IProps) {
    const locales = useAvailableSubcommunityLocales();

    if (locales === null) {
        return <Loader small padding={10} />;
    }

    const classes = subcommunityChooserClasses();

    return (
        <div>
            {Object.values(locales).map(locale => {
                return (
                    <DropDownItemButton key={locale.localeID} onClick={() => props.onChange(locale.localeKey)}>
                        <span className={classes.row}>
                            <LocaleDisplayer localeContent={locale.localeKey} displayLocale={locale.localeKey} />
                            <RightChevronIcon className={classes.rowArrow} />
                        </span>
                    </DropDownItemButton>
                );
            })}
        </div>
    );
}
