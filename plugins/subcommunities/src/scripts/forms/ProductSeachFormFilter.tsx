/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { useEffect } from "react";
import { IComboBoxOption } from "@library/features/search/SearchBar";
import { t } from "@vanilla/i18n";
import { useProducts } from "@subcommunities/products/productSelectors";
import { getSiteSection } from "@library/utility/appUtils";
import SelectOne from "@library/forms/select/SelectOne";
import { useSearchFilters } from "@library/contexts/SearchFilterContext";

interface IProps {
    default: "all" | "current";
    searchDomain: string;
}

/**
 * Search filter to add into the search form.
 */
export function ProductSearchFormFilter(props: IProps) {
    const { searchDomain } = props;
    const { productsById } = useProducts();
    const { updateQueryValuesForDomain, getQueryValuesForDomain } = useSearchFilters();
    const existingValues = getQueryValuesForDomain(props.searchDomain);

    const currentSiteSection = getSiteSection();
    const siteSectionGroupOptions: IComboBoxOption[] = [
        {
            // All
            value: "all",
            label: t("(All)"),
        },
        {
            value: currentSiteSection.sectionGroup,
            // This is technically the "subcommunity" name
            // Once we have translation of product names we will display that name instead.
            label: currentSiteSection.name,
        },
    ];

    const defaultSectionGroup = props.default === "all" ? siteSectionGroupOptions[0] : siteSectionGroupOptions[1];
    const placeHolderText = defaultSectionGroup.label;
    let value = siteSectionGroupOptions.find(option => option.value === existingValues.siteSectionGroup);

    const formSectionGroup = existingValues.siteSectionGroup;
    const defaultValueToSet = defaultSectionGroup.value.toString();

    useEffect(() => {
        // On mount be sure to set our default value into the search form if it's not set.
        if (!formSectionGroup) {
            updateQueryValuesForDomain(searchDomain, { siteSectionGroup: defaultValueToSet });
        }
        // Always clear the form on unmount.
        return () => {
            updateQueryValuesForDomain(searchDomain, { siteSectionGroup: null });
        };
    }, []);

    if (Object.values(productsById).length <= 1) {
        // No need to offer the dropdown if don't have products.
        return null;
    }

    if (currentSiteSection.sectionGroup === "vanilla") {
        // We are in the default site section
        // We don't have a "real" current product.
        return null;
    }

    return (
        <SelectOne
            options={siteSectionGroupOptions}
            label={t("Product")}
            value={value}
            placeholder={placeHolderText}
            isClearable={false}
            onChange={value => {
                if (!value) {
                    return;
                }
                updateQueryValuesForDomain(searchDomain, { siteSectionGroup: value.value.toString() });
            }}
        />
    );
}
