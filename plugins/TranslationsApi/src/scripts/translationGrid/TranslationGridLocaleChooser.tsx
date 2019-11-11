/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { useEffect } from "react";
import { translationGridClasses } from "./TranslationGridStyles";
import { panelListVariables, panelListClasses } from "@library/layout/panelListStyles";
import classNames from "classnames";
import SelectBox, { ISelectBoxItem } from "@library/forms/select/SelectBox";
import { useLocaleInfo, LocaleDisplayer } from "@vanilla/i18n";
import { AlertIcon } from "@library/icons/common";

interface IProps {
    onChange: (selectedLocale: string) => void;
    selectedLocale: string | null;
    sourceLocale: string;
}

export function TranslationGridLocaleChooser(props: IProps) {
    const classes = translationGridClasses();
    const classesPanelList = panelListClasses();
    const { locales } = useLocaleInfo();

    useEffect(() => {
        if (!props.selectedLocale) {
            props.onChange(locales[0].localeKey);
        }
    }, [props.selectedLocale, props.onChange]);

    const options = locales
        .filter(locale => locale.localeKey !== props.sourceLocale)
        .map(
            (locale): ISelectBoxItem => {
                return {
                    value: locale.localeKey,
                    name: locale.localeKey,
                    icon: (
                        <span tabIndex={0}>
                            <AlertIcon className={"selectBox-selectedIcon"} />
                        </span>
                    ),
                    content: (
                        <>
                            <LocaleDisplayer displayLocale={locale.localeKey} localeContent={locale.localeKey} />
                        </>
                    ),
                };
            },
        );

    const selectedOption = options.find(option => option.value === props.selectedLocale);

    return (
        <div className={classes.languageDropdown}>
            <div className={classNames("otherLanguages", "panelList", classesPanelList.root)}>
                <SelectBox
                    label={"test"}
                    widthOfParent={true}
                    value={selectedOption}
                    onChange={option => props.onChange(option.value)}
                    options={options}
                />
            </div>
        </div>
    );
}
