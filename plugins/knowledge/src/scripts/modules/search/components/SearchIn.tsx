/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { RadioInputsAsButtonGroup } from "@library/forms/radioAsButtons/RadioInputsAsButtonGroup";
import { t } from "@vanilla/i18n";
import React from "react";
import RadioInputAsButton from "@library/forms/radioAsButtons/RadioInputAsButton";
import { searchInClasses } from "@knowledge/modules/search/components/searchIn.styles";

export interface ISearchInButton {
    label: string;
    icon: JSX.Element;
    data: number | string;
}

interface IProps {
    activeItem?: number | string;
    setData: (data: number | string) => void;
    filters: ISearchInButton[];
    endFilters?: ISearchInButton[]; // At the end, separated by vertical line
}

/**
 * Implements filters for search page
 */
export function SearchIn(props: IProps) {
    const { filters = [], setData, endFilters = [], activeItem } = props;
    const hasEndFilters = endFilters.length !== 0;
    if (filters.length === 0 && !hasEndFilters) {
        return null; // no filters
    }
    const classes = searchInClasses();
    return (
        <RadioInputsAsButtonGroup accessibleTitle={t("Search in:")} setData={setData} activeItem={activeItem}>
            <>
                {filters.map((filter, i) => {
                    return (
                        <RadioInputAsButton key={i} {...filter} active={!!activeItem && filter.data === activeItem} />
                    );
                })}
                {hasEndFilters && (
                    <>
                        <span className={classes.separator} role="separator" />
                        {endFilters.map((filter, i) => {
                            return (
                                <RadioInputAsButton {...filter} active={!!activeItem && filter.data === activeItem} />
                            );
                        })}
                    </>
                )}
            </>
        </RadioInputsAsButtonGroup>
    );
}
