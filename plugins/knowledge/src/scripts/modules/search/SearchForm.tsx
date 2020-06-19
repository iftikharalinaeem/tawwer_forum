/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { SearchFormResults } from "@knowledge/modules/search/SearchFormResults";
import { useSearchPageActions } from "@knowledge/modules/search/SearchPageActions";
import UnifySearchPageActions from "@knowledge/modules/search/UnifySearchPageActions";
import {
    INITIAL_SEARCH_FORM,
    UnifySearchDomain,
    useSearchPageData,
} from "@knowledge/modules/search/unifySearchPageReducer";
import { LoadStatus } from "@library/@types/api/core";
import SearchBar from "@library/features/search/SearchBar";
import { searchBarClasses } from "@library/features/search/searchBarStyles";
import SearchOption from "@library/features/search/SearchOption";
import { ButtonTypes } from "@library/forms/buttonTypes";
import TitleBar from "@library/headers/TitleBar";
import { SearchFilterAllIcon, TypeArticlesIcon, TypeDiscussionsIcon } from "@library/icons/searchIcons";
import Container from "@library/layout/components/Container";
import { Devices, useDevice } from "@library/layout/DeviceContext";
import Drawer from "@library/layout/drawer/Drawer";
import { PageHeading } from "@library/layout/PageHeading";
import { pageTitleClasses } from "@library/layout/pageTitleStyles";
import PanelLayout, { PanelWidget } from "@library/layout/PanelLayout";
import DocumentTitle from "@library/routing/DocumentTitle";
import QueryString from "@library/routing/QueryString";
import { SearchInFilter } from "@library/search/SearchInFilter";
import { typographyClasses } from "@library/styles/typographyStyles";
import { t } from "@library/utility/appUtils";
import Banner from "@vanilla/library/src/scripts/banner/Banner";
import { FilterPanelAll } from "@vanilla/library/src/scripts/search/panels/FilterPanelAll";
import { SearchFilterPanelArticles } from "@vanilla/library/src/scripts/search/panels/FilterPanelArticles";
import { SearchFilterPanelDiscussions } from "@vanilla/library/src/scripts/search/panels/FilterPanelDiscussions";
import { useSearchForm } from "@vanilla/library/src/scripts/search/SearchFormContext";
import { notEmpty } from "@vanilla/utils";
import classNames from "classnames";
import debounce from "lodash/debounce";
import pick from "lodash/pick";
import pickBy from "lodash/pickBy";
import qs from "qs";
import * as React from "react";
import { useCallback, useEffect } from "react";
import { useHistory, useLocation } from "react-router";
import { useLastValue } from "@vanilla/react-utils";
import { ALL_CONTENT_DOMAIN_NAME } from "@vanilla/library/src/scripts/search/searchTypes";

interface IProps {
    placeholder?: string;
}

function SearchForm(props: IProps) {
    const {
        form,
        updateForm,
        search,
        results,
        getDomains,
        getCurrentDomain,
        getDefaultFormValues,
    } = useSearchForm<{}>();
    const device = useDevice();
    const isMobile = device === Devices.MOBILE || device === Devices.XS;
    const classes = pageTitleClasses();
    useInitialQueryParamSync();

    const debouncedSearch = useCallback(
        debounce(() => {
            search();
        }, 800),
        [search],
    );

    const { domain } = form;
    const lastDomain = useLastValue(form.domain);
    useEffect(() => {
        if (lastDomain && domain !== lastDomain) {
            search();
        }
    }, [lastDomain, domain, search]);

    const currentDomain = getCurrentDomain();
    let currentFilter = <currentDomain.PanelComponent />;

    return (
        <DocumentTitle title={form.query ? form.query : t("Search Results")}>
            <TitleBar title={t("Search")} />
            <Banner isContentBanner />
            <Container>
                <QueryString value={form} defaults={getDefaultFormValues()} />
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
                                    updateForm({ domain: newDomain });
                                }}
                                activeItem={form.domain}
                                filters={getDomains().map(domain => {
                                    return {
                                        label: domain.name,
                                        icon: domain.icon,
                                        data: domain.key,
                                    };
                                })}
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

function useInitialQueryParamSync() {
    const { updateForm, form, search } = useSearchForm();
    const location = useLocation();

    useEffect(() => {
        const { search: browserQuery } = location;
        const queryForm = qs.parse(browserQuery.replace(/^\?/, ""));

        for (const [key, value] of Object.entries(queryForm)) {
            if (value === "true") {
                queryForm[key] = true;
            }

            if (value === "false") {
                queryForm[key] = false;
            }

            if (typeof value === "string" && Number.isInteger(parseInt(value, 10))) {
                queryForm[key] = parseInt(value, 10);
            }
        }

        queryForm.initialized = true;

        updateForm(queryForm);
        // Only for first initialization.
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    const { initialized } = form;
    const lastinitialized = useLastValue(form.initialized);
    useEffect(() => {
        if (!lastinitialized && initialized) {
            console.error("First init");
            search();
        }
    }, [search, lastinitialized, initialized]);
}

export default SearchForm;
