/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { SearchFormFilters } from "@knowledge/modules/search/SearchFormFilters";
import PanelEmptyColumn from "@knowledge/modules/search/components/PanelEmptyColumn";
import { SearchFormResults } from "@knowledge/modules/search/SearchFormResults";
import { useSearchPageActions } from "@knowledge/modules/search/SearchPageActions";
import { INITIAL_SEARCH_FORM, useSearchPageData } from "@knowledge/modules/search/searchPageReducer";
import { LoadStatus } from "@library/@types/api/core";
import { IWithSearchProps, withSearch } from "@library/contexts/SearchContext";
import SearchBar from "@library/features/search/SearchBar";
import SearchOption from "@library/features/search/SearchOption";
import { ButtonTypes } from "@library/forms/buttonStyles";
import TitleBar from "@library/headers/TitleBar";
import Container from "@library/layout/components/Container";
import { Devices, useDevice } from "@library/layout/DeviceContext";
import Drawer from "@library/layout/drawer/Drawer";
import PanelLayout, { PanelWidget } from "@library/layout/PanelLayout";
import DocumentTitle from "@library/routing/DocumentTitle";
import QueryString from "@library/routing/QueryString";
import { t, getSiteSection } from "@library/utility/appUtils";
import debounce from "lodash/debounce";
import qs from "qs";
import * as React from "react";
import { useEffect, useCallback } from "react";
import { useHistory } from "react-router";
import { useSearchFilters } from "@library/contexts/SearchFilterContext";

interface IProps extends IWithSearchProps {
    placeholder?: string;
}

function SearchForm(props: IProps) {
    const { form, results } = useSearchPageData();
    const device = useDevice();
    const isMobile = device === Devices.MOBILE || device === Devices.XS;
    const isFullWidth = [Devices.DESKTOP, Devices.NO_BLEED].includes(device); // This compoment doesn't care about the no bleed, it's the same as desktop

    useQueryParamSynchronization();
    useSearchContextValueSync();

    const { search, updateForm } = useSearchPageActions();
    const debouncedSearch = useCallback(
        debounce(() => {
            search();
        }, 800),
        [search],
    );

    let knowledgeBase = form.kb || form.knowledgeBaseID;
    let knowledgeBaseID = form.kb?.value || form.knowledgeBaseID;

    let queryParam = knowledgeBase ? "kb" : "siteSectionGroup";
    let queryValue =
        queryParam === "kb" ? { label: form.kb?.label, value: knowledgeBaseID } : getSiteSection().sectionGroup;

    const queryParamDefaults = {
        ...INITIAL_SEARCH_FORM,
        [queryParam]: queryValue,
    };

    return (
        <DocumentTitle title={form.query ? form.query : t("Search Results")}>
            <TitleBar title={t("Search")} />
            <Container>
                <QueryString value={form} defaults={queryParamDefaults} />
                <PanelLayout
                    className="hasLargePadding"
                    leftTop={isFullWidth && <PanelEmptyColumn />}
                    middleTop={
                        <>
                            <PanelWidget>
                                <SearchBar
                                    placeholder={props.placeholder}
                                    onChange={newQuery => updateForm({ query: newQuery })}
                                    value={form.query}
                                    onSearch={debouncedSearch}
                                    isLoading={results.status === LoadStatus.LOADING}
                                    optionComponent={SearchOption}
                                    triggerSearchOnClear={true}
                                    title={t("Search")}
                                    titleAsComponent={t("Search")}
                                    handleOnKeyDown={event => {
                                        if (event.key === "Enter") {
                                            debouncedSearch();
                                        }
                                    }}
                                    disableAutocomplete={true}
                                    buttonBaseClass={ButtonTypes.PRIMARY}
                                    needsPageTitle={true}
                                />
                            </PanelWidget>
                            {isMobile && (
                                <Drawer title={t("Filter Results")}>
                                    <SearchFormFilters hideTitle={true} />
                                </Drawer>
                            )}
                        </>
                    }
                    middleBottom={<SearchFormResults />}
                    rightTop={
                        !isMobile && (
                            <PanelWidget>
                                <SearchFormFilters />
                            </PanelWidget>
                        )
                    }
                />
            </Container>
        </DocumentTitle>
    );
}

function useSearchContextValueSync() {
    const { updateForm } = useSearchPageActions();
    const { form } = useSearchPageData();
    const { getQueryValuesForDomain, updateQueryValuesForDomain } = useSearchFilters();
    const extraValues = getQueryValuesForDomain(form.domain);
    const { location } = useHistory();
    const queryString = location.search;
    const initialForm = qs.parse(queryString.replace(/^\?/, ""));

    if (initialForm.knowledgeBaseID && !form.kb) {
        form.kb = { label: "", value: initialForm.knowledgeBaseID };
    }

    if (extraValues.knowledgeBaseID || form.kb) {
        extraValues.siteSectionGroup = undefined;
    }

    useEffect(() => {
        updateForm(extraValues);
    }, [updateForm, extraValues]);
}

function useQueryParamSynchronization() {
    const { updateForm, search, reset } = useSearchPageActions();
    const { updateQueryValuesForDomain } = useSearchFilters();
    const { location } = useHistory();
    const queryString = location.search;
    /**
     * Initialize the form values from a query string.
     *
     * Many of these values can be a bit of a PITA the put together manually, but all form values are persisted there.
     *
     * @param queryString The query string to initialize from.
     */
    useEffect(() => {
        const initialForm = qs.parse(queryString.replace(/^\?/, ""));

        if ("authors" in initialForm) {
            initialForm.authors.map(option => {
                option.value = Number.parseInt(option.value, 10);
            });
        }

        if ("includeDeleted" in initialForm) {
            initialForm.includeDeleted = JSON.parse(initialForm.includeDeleted);
        }

        updateForm(initialForm);

        if (initialForm.siteSectionGroup) {
            const initialDomain = initialForm.domain || INITIAL_SEARCH_FORM.domain;
            // Syncing back to provider.
            updateQueryValuesForDomain(initialDomain, { siteSectionGroup: initialForm.siteSectionGroup });
        }
        void search();

        // Cleanup when we leave the page.
        return () => {
            reset();
        };
    }, []); // Only run when we first initilize the page.
}

export default withSearch(SearchForm);
