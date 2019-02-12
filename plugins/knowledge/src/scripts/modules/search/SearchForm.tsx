/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import * as React from "react";
import Container from "@library/components/layouts/components/Container";
import { Devices } from "@library/components/DeviceChecker";
import { withDevice } from "@library/contexts/DeviceContext";
import PanelLayout, { PanelWidget, PanelWidgetVerticalPadding } from "@library/components/layouts/PanelLayout";
import { t } from "@library/application";
import AdvancedSearch from "@knowledge/modules/search/components/AdvancedSearch";
import SearchBar from "@library/components/forms/select/SearchBar";
import SearchResults from "@knowledge/modules/common/SearchResults";
import PanelEmptyColumn from "@knowledge/modules/search/components/PanelEmptyColumn";
import { connect } from "react-redux";
import SearchPageModel, { ISearchPageState, SearchDomain } from "@knowledge/modules/search/SearchPageModel";
import SearchPageActions, { ISearchFormActionProps } from "@knowledge/modules/search/SearchPageActions";
import QueryString from "@library/components/navigation/QueryString";
import qs from "qs";
import { LoadStatus } from "@library/@types/api";
import { IResult } from "@knowledge/modules/common/SearchResult";
import { ISearchResult, ArticleStatus } from "@knowledge/@types/api";
import { SearchResultMeta } from "@knowledge/modules/common/SearchResultMeta";
import DocumentTitle from "@library/components/DocumentTitle";
import SearchOption from "@library/components/search/SearchOption";
import Drawer from "@library/components/drawer/Drawer";
import { withSearch, IWithSearchProps } from "@library/contexts/SearchContext";
import VanillaHeader from "@library/components/headers/VanillaHeader";
import LinkAsButton from "@library/components/LinkAsButton";
import { ButtonBaseClass } from "@library/components/forms/Button";
import { compose } from "@library/components/icons/header";
import SearchPagination from "./components/SearchPagination";
import FullPageLoader from "@library/components/FullPageLoader";

interface IProps extends ISearchFormActionProps, ISearchPageState, IWithSearchProps {
    placeholder?: string;
    device: Devices;
}

class SearchForm extends React.Component<IProps> {
    public render() {
        const { device, form } = this.props;
        const isMobile = device === Devices.MOBILE;
        const isFullWidth = [Devices.DESKTOP, Devices.NO_BLEED].includes(device); // This compoment doesn't care about the no bleed, it's the same as desktop

        return (
            <DocumentTitle title={form.query ? form.query : t("Search Results")}>
                <VanillaHeader title={t("Search")} />
                <Container>
                    <QueryString value={this.props.form} defaults={SearchPageModel.INITIAL_FORM} />
                    <PanelLayout
                        device={this.props.device}
                        className="hasLargePadding"
                        leftTop={isFullWidth && <PanelEmptyColumn />}
                        middleTop={
                            <>
                                <PanelWidget>
                                    <SearchBar
                                        placeholder={this.props.placeholder}
                                        onChange={this.handleSearchChange}
                                        loadOptions={this.autocomplete}
                                        value={this.props.form.query}
                                        isBigInput={true}
                                        onSearch={this.props.searchActions.search}
                                        isLoading={this.props.results.status === LoadStatus.LOADING}
                                        optionComponent={SearchOption}
                                        triggerSearchOnClear={true}
                                        title={t("Search")}
                                        titleAsComponent={t("Search")}
                                        autocomplete={false}
                                    />
                                </PanelWidget>
                                {isMobile && (
                                    <Drawer title={t("Filter Results")}>
                                        <AdvancedSearch hideTitle={true} />
                                    </Drawer>
                                )}
                            </>
                        }
                        middleBottom={
                            <PanelWidgetVerticalPadding>{this.renderSearchResults()}</PanelWidgetVerticalPadding>
                        }
                        rightTop={
                            !isMobile && (
                                <PanelWidget>
                                    <AdvancedSearch />
                                </PanelWidget>
                            )
                        }
                    />
                </Container>
            </DocumentTitle>
        );
    }

    /**
     * @inheritdoc
     */
    public componentDidMount() {
        this.initializeFromQueryString(window.location.search);
    }

    /**
     * @inheritdoc
     */
    public componentWillUnmount() {
        this.props.searchActions.reset();
    }

    /**
     * Initialize the form values from a query string.
     *
     * Many of these values can be a bit of a PITA the put together manually, but all form values are persisted there.
     *
     * @param queryString The query string to initialize from.
     */
    private initializeFromQueryString(queryString: string) {
        const initialForm = qs.parse(queryString.replace(/^\?/, ""));

        if ("authors" in initialForm) {
            initialForm.authors.map(option => {
                option.value = Number.parseInt(option.value, 10);
            });
        }

        if ("includeDeleted" in initialForm) {
            initialForm.includeDeleted = JSON.parse(initialForm.includeDeleted);
        }

        this.props.searchActions.updateForm(initialForm);
        void this.props.searchActions.search();
    }

    /**
     * Simple form setter for the search bar.
     */
    private handleSearchChange = (value: string) => {
        this.props.searchActions.updateForm({ query: value });
    };

    private autocomplete = (query: string) => {
        return this.props.searchOptionProvider.autocomplete(query, {
            global: this.props.form.domain === SearchDomain.EVERYWHERE,
        });
    };

    /**
     * Unwrap loaded results and map them into the proper shape.
     */
    private renderSearchResults(): React.ReactNode {
        switch (this.props.results.status) {
            case LoadStatus.PENDING:
            case LoadStatus.LOADING:
                return <FullPageLoader />;
            case LoadStatus.ERROR:
                return null;
            case LoadStatus.SUCCESS:
                const { next, prev } = this.props.pages;
                let paginationNextClick: React.MouseEventHandler | undefined;
                let paginationPreviousClick: React.MouseEventHandler | undefined;

                if (next) {
                    paginationNextClick = e => {
                        void this.props.searchActions.search(next);
                    };
                }
                if (prev) {
                    paginationPreviousClick = e => {
                        void this.props.searchActions.search(prev);
                    };
                }

                return (
                    <>
                        <SearchResults results={this.props.results.data!.map(this.mapResult)} />
                        <SearchPagination onNextClick={paginationNextClick} onPreviousClick={paginationPreviousClick} />
                    </>
                );
        }
    }

    /**
     * Map a search API response into what the <SearchResults /> component is expecting.
     *
     * @param searchResult The API search result to map.
     */
    private mapResult(searchResult: ISearchResult): IResult {
        const crumbs = searchResult.breadcrumbs || [];
        return {
            name: searchResult.name,
            excerpt: searchResult.body,
            meta: (
                <SearchResultMeta
                    status={searchResult.status}
                    type={searchResult.recordType}
                    updateUser={searchResult.updateUser!}
                    dateUpdated={searchResult.dateUpdated}
                    crumbs={crumbs}
                />
            ),
            url: searchResult.url,
            location: crumbs,
        };
    }
}

const withRedux = connect(
    SearchPageModel.mapStateToProps,
    SearchPageActions.mapDispatchToProps,
);

export default withRedux(withSearch(withDevice(SearchForm)));
