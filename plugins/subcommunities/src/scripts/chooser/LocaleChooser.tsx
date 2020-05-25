/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */
import DropDownItemButton from "@library/flyouts/items/DropDownItemButton";
import { RightChevronIcon } from "@library/icons/common";
import { subcommunityChooserClasses } from "@subcommunities/chooser/subcommunityChooserStyles";
import { useSubcommunitiesOrLocales } from "@subcommunities/subcommunities/subcommunitySelectors";
import { getCurrentLocale, LocaleDisplayer } from "@vanilla/i18n";
import DropDownItemLink from "@vanilla/library/src/scripts/flyouts/items/DropDownItemLink";
import React from "react";

interface IProps {
    value: string | null;
    onChange: (value: string) => void;
}

/**
 * Component for choosing a locale.
 */
export function LocaleChooser(props: IProps) {
    const subcommunitiesOrLocale = useSubcommunitiesOrLocales();

    const classes = subcommunityChooserClasses();
    const currentLocale = getCurrentLocale();

    return (
        <div>
            {subcommunitiesOrLocale.map((localeOrSubcommunity, i) => {
                if ("subcommunityID" in localeOrSubcommunity) {
                    const subcommunity = localeOrSubcommunity;
                    return (
                        <DropDownItemLink
                            key={i}
                            to={subcommunity.url}
                            isChecked={subcommunity.locale === getCurrentLocale()}
                        >
                            <span className={classes.row}>
                                <LocaleDisplayer
                                    localeContent={subcommunity.locale}
                                    displayLocale={subcommunity.locale}
                                />
                            </span>
                        </DropDownItemLink>
                    );
                } else {
                    const locale = localeOrSubcommunity;
                    return (
                        <DropDownItemButton key={i} onClick={() => props.onChange(locale.localeKey)}>
                            <span className={classes.row}>
                                <LocaleDisplayer localeContent={locale.localeKey} displayLocale={locale.localeKey} />
                                <RightChevronIcon className={classes.rowArrow} />
                            </span>
                        </DropDownItemButton>
                    );
                }
            })}
        </div>
    );
}
