/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { SearchFormFilters } from "@knowledge/modules/search/SearchFormFilters";
import PanelEmptyColumn from "@knowledge/modules/search/components/PanelEmptyColumn";
import { SearchFormResults } from "@knowledge/modules/search/SearchFormResults";
import { useSearchPageActions } from "@knowledge/modules/search/SearchPageActions";
import { LoadStatus } from "@library/@types/api/core";
import { IWithSearchProps, withSearch } from "@library/contexts/SearchContext";
import SearchBar from "@library/features/search/SearchBar";
import SearchOption from "@library/features/search/SearchOption";
import { ButtonTypes } from "@library/forms/buttonTypes";
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
import { useEffect, useCallback, useState } from "react";
import { useHistory, useLocation } from "react-router";
import { useSearchFilters } from "@library/contexts/SearchFilterContext";
import Banner from "@vanilla/library/src/scripts/banner/Banner";
import PageTitle from "@knowledge/modules/common/PageTitle";
import { pageTitleClasses } from "@library/layout/pageTitleStyles";
import classNames from "classnames";
import { typographyClasses } from "@library/styles/typographyStyles";
import { iconClasses } from "@library/icons/iconStyles";
import { searchBarClasses } from "@library/features/search/searchBarStyles";
import { PageHeading } from "@library/layout/PageHeading";
import { ISearchInButton, SearchInFilter } from "@library/search/SearchInFilter";
import {
    SearchFilterAllIcon,
    TypeArticlesIcon,
    TypeCategoriesAndGroupsIcon,
    TypeDiscussionsIcon,
} from "@library/icons/searchIcons";
import UnifySearchPageActions, { useUnifySearchPageActions } from "@knowledge/modules/search/UnifySearchPageActions";
import pickBy from "lodash/pickBy";
import pick from "lodash/pick";
import { notEmpty } from "@vanilla/utils";
import {
    INITIAL_SEARCH_FORM,
    UnifySearchDomain,
    useSearchPageData,
} from "@knowledge/modules/search/unifySearchPageReducer";
import { SearchPanelFilter } from "@vanilla/library/src/scripts/search/panels/SearchPanelFilter.story";
import { SearchFilterPanelArticles } from "@vanilla/library/src/scripts/search/panels/FilterPanelArticles";
import { FilterPanelAll } from "@vanilla/library/src/scripts/search/panels/FilterPanelAll";
import { SearchFilterPanelDiscussions } from "@vanilla/library/src/scripts/search/panels/FilterPanelDiscussions";

interface IProps extends IWithSearchProps {
    placeholder?: string;
}

function SearchForm(props: IProps) {
    const { unifySearch, updateForm } = useUnifySearchPageActions();
    const { form, results } = useSearchPageData();
    const device = useDevice();
    const isMobile = device === Devices.MOBILE || device === Devices.XS;
    const classes = pageTitleClasses();
    useQueryParamSynchronization();
    useSearchContextValueSync();

    const debouncedSearch = useCallback(
        debounce(() => {
            unifySearch();
        }, 800),
        [unifySearch],
    );

    let currentFilter: React.ReactNode = null;

    switch (form.domain) {
        case UnifySearchDomain.ARTICLES:
            currentFilter = <SearchFilterPanelArticles />;
            break;
        case UnifySearchDomain.DISCUSSIONS:
            currentFilter = <SearchFilterPanelDiscussions />;
            break;
        case UnifySearchDomain.ALL_CONTENT:
        default:
            currentFilter = <FilterPanelAll />;
            break;
    }

    return (
        <DocumentTitle title={form.query ? form.query : t("Search Results")}>
            <TitleBar title={t("Search")} />
            <Banner isContentBanner />
            <Container>
                <QueryString value={form} defaults={INITIAL_SEARCH_FORM} />
                <PanelLayout
                    className="hasLargePadding"
                    middleTop={
                        <>
                            <PanelWidget>
                                <PageHeading
                                    className={classNames(
                                        "searchBar-heading",
                                        searchBarClasses().heading,
                                        classes.smallBackLink,
                                    )}
                                    headingClassName={classNames(typographyClasses().pageTitle)}
                                    title={"Search"}
                                    includeBackLink={true}
                                    isCompactHeading={true}
                                />
                                <SearchBar
                                    placeholder={props.placeholder}
                                    onChange={newQuery => updateForm({ query: newQuery })}
                                    value={form.query}
                                    onSearch={debouncedSearch}
                                    isLoading={results.status === LoadStatus.LOADING}
                                    optionComponent={SearchOption}
                                    triggerSearchOnClear={true}
                                    titleAsComponent={t("Search")}
                                    handleOnKeyDown={event => {
                                        if (event.key === "Enter") {
                                            debouncedSearch();
                                        }
                                    }}
                                    disableAutocomplete={true}
                                    buttonBaseClass={ButtonTypes.PRIMARY}
                                    needsPageTitle={false}
                                />
                            </PanelWidget>
                            <SearchInFilter
                                setData={newDomain => {
                                    updateForm({ domain: newDomain as UnifySearchDomain });
                                    unifySearch();
                                }}
                                activeItem={form.domain}
                                filters={[
                                    {
                                        label: t("All Content"),
                                        icon: <SearchFilterAllIcon />,
                                        data: UnifySearchDomain.ALL_CONTENT,
                                    },
                                    {
                                        label: t("Discussions"),
                                        icon: <TypeDiscussionsIcon />,
                                        data: UnifySearchDomain.DISCUSSIONS,
                                    },
                                    {
                                        label: t("Articles"),
                                        icon: <TypeArticlesIcon />,
                                        data: UnifySearchDomain.ARTICLES,
                                    },
                                ]}
                            />
                            {isMobile && (
                                <PanelWidget>
                                    <Drawer title={t("Filter Results")}>{currentFilter}</Drawer>
                                </PanelWidget>
                            )}
                        </>
                    }
                    middleBottom={<SearchFormResults />}
                    rightTop={!isMobile && <PanelWidget>{currentFilter}</PanelWidget>}
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

    // if (initialForm.knowledgeBaseID && !form.kb) {
    //     form.kb = { label: "", value: initialForm.knowledgeBaseID };
    // }

    // if (extraValues.knowledgeBaseID || form.kb) {
    //     extraValues.siteSectionGroup = undefined;
    // }

    // useEffect(() => {
    //     updateForm(extraValues);
    // }, [updateForm, extraValues]);
}

function useQueryParamSynchronization() {
    const { updateForm: updateForm, unifySearch } = useUnifySearchPageActions();
    const location = useLocation();

    useEffect(() => {
        const { search } = location;
        const queryForm = qs.parse(search.replace(/^\?/, ""));
        const form = pickBy(pick(queryForm, UnifySearchPageActions.ALL_FORM_ENTRIES), notEmpty);
        updateForm(form);
        unifySearch();
        // Only for first initialization.
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);
}

export default withSearch(SearchForm);
