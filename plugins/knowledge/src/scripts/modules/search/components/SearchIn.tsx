/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { t } from "@vanilla/i18n";
import React from "react";
import RadioInputAsButton from "@library/forms/radioAsButtons/RadioInputAsButton";
import { searchInClasses } from "@knowledge/modules/search/components/searchIn.styles";
import { RadioGroup } from "@vanilla/library/src/scripts/forms/radioAsButtons/RadioGroup";

export interface ISearchInButton {
    label: string;
    icon: JSX.Element;
    data: number | string;
}

interface IProps {
    activeItem?: any;
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
        <RadioGroup accessibleTitle={t("Search in:")} setData={setData} activeItem={activeItem}>
            {/*{filters.map((filter, i) => {*/}
            {/*    return <RadioInputAsButton key={i} {...filter} />;*/}
            {/*})}*/}
            {/*{hasEndFilters && (*/}
            {/*    <>*/}
            {/*        <span className={classes.separator} role="separator" />*/}
            {/*        {endFilters.map((filter, i) => {*/}
            {/*            return <RadioInputAsButton key={i} {...filter} />;*/}
            {/*        })}*/}
            {/*    </>*/}
            {/*)}*/}
        </RadioGroup>
    );
}
