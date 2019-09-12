/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */
import DropDownItemButton from "@library/flyouts/items/DropDownItemButton";
import { RightChevronIcon } from "@library/icons/common";
import Loader from "@library/loaders/Loader";
import { subcommunityChooserClasses } from "@subcommunities/chooser/subcommunityChooserStyles";
import { useAvailableLocales } from "@subcommunities/subcommunities/subcommunitySelectors";
import React from "react";
import { LocaleDisplayer } from "@subcommunities/chooser/LocaleDisplayer";

interface IProps {
    value: string | null;
    onChange: (value: string) => void;
}

/**
 * Component for choosing a locale.
 */
export function LocaleChooser(props: IProps) {
    const locales = useAvailableLocales();

    if (locales === null) {
        return <Loader small padding={10} />;
    }

    const classes = subcommunityChooserClasses();

    return (
        <div>
            {Object.values(locales).map(locale => {
                return (
                    <DropDownItemButton key={locale.key} onClick={() => props.onChange(locale.key)}>
                        <span className={classes.row}>
                            <LocaleDisplayer localeContent={locale.key} />
                            <RightChevronIcon className={classes.rowArrow} />
                        </span>
                    </DropDownItemButton>
                );
            })}
        </div>
    );
}
