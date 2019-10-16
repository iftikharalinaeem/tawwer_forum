/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import AdvancedSearch from "@knowledge/modules/search/components/AdvancedSearch";
import PanelEmptyColumn from "@knowledge/modules/search/components/PanelEmptyColumn";
import SearchPageActions, { ISearchFormActionProps } from "@knowledge/modules/search/SearchPageActions";
import SearchPageModel, { ISearchPageState } from "@knowledge/modules/search/SearchPageModel";
import { LoadStatus } from "@library/@types/api/core";
import { IWithSearchProps, withSearch } from "@library/contexts/SearchContext";
import SearchBar from "@library/features/search/SearchBar";
import SearchOption from "@library/features/search/SearchOption";
import TitleBar from "@library/headers/TitleBar";
import Container from "@library/layout/components/Container";
import { withDevice, Devices } from "@library/layout/DeviceContext";
import Drawer from "@library/layout/drawer/Drawer";
import PanelLayout, { PanelWidget, PanelWidgetVerticalPadding } from "@library/layout/PanelLayout";
import Loader from "@library/loaders/Loader";
import { IResult } from "@library/result/Result";
import DocumentTitle from "@library/routing/DocumentTitle";
import QueryString from "@library/routing/QueryString";
import { t } from "@library/utility/appUtils";
import debounce from "lodash/debounce";
import qs from "qs";
import * as React from "react";
import { connect } from "react-redux";
import SearchPagination from "./components/SearchPagination";
import { ButtonTypes } from "@library/forms/buttonStyles";
import { ISearchResult } from "@knowledge/@types/api/search";
import ResultList from "@library/result/ResultList";
import { ResultMeta } from "@library/result/ResultMeta";
import { AnalyticsData } from "@library/analytics/AnalyticsData";
import { hashString } from "@vanilla/utils";

interface IProps extends ISearchFormActionProps, ISearchPageState, IWithSearchProps {
    placeholder?: string;
    device: Devices;
}

interface IState {
    lastQuery: string | null;
}

class SearchForm extends React.Component<IProps, IState> {
    public constructor(props) {
        super(props);
        this.state = {
            lastQuery: null,
        };
    }

    public render() {
        const { device, form } = this.props;
        const isMobile = device === Devices.MOBILE || device === Devices.XS;
        const isFullWidth = [Devices.DESKTOP, Devices.NO_BLEED].includes(device); // This compoment doesn't care about the no bleed, it's the same as desktop
        return (
            <DocumentTitle title={form.query ? form.query : t("Search Results")}>
                <TitleBar title={t("Search")} />
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
                                        value={this.props.form.query}
                                        isBigInput={true}
                                        onSearch={this.props.searchActions.search}
                                        isLoading={this.props.results.status === LoadStatus.LOADING}
                                        optionComponent={SearchOption}
                                        triggerSearchOnClear={true}
                                        title={t("Search")}
                                        titleAsComponent={t("Search")}
                                        handleOnKeyDown={this.handleKeyDown}
                                        disableAutocomplete={true}
                                        buttonBaseClass={ButtonTypes.PRIMARY}
                                    />
                                </PanelWidget>
                                {isMobile && (
                                    <Drawer title={t("Filter Results")}>
                                        <AdvancedSearch hideTitle={true} />
                                    </Drawer>
                                )}
                            </>
                        }
                        middleBottom={this.renderSearchResults()}
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
        this.searchOnDebounce();
    };

    /**
     * Unwrap loaded results and map them into the proper shape.
     */
    private renderSearchResults(): React.ReactNode {
        switch (this.props.results.status) {
            case LoadStatus.PENDING:
            case LoadStatus.LOADING:
                return <Loader />;
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
                        <AnalyticsData
                            uniqueKey={hashString(this.props.form.query + JSON.stringify(this.props.pages))}
                        />
                        <ResultList results={this.props.results.data!.map(this.mapResult)} />
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
                <ResultMeta
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

    private searchOnDebounce = () => {
        if (this.props.form.query !== this.state.lastQuery) {
            debounce(() => {
                // tslint:disable-next-line:no-floating-promises
                this.props.searchActions.search();
            }, 800);
        }
    };

    private handleKeyDown = e => {
        if (e.key === "Enter") {
            // tslint:disable-next-line:no-floating-promises
            this.props.searchActions.search();
        }
    };
}

const withRedux = connect(
    SearchPageModel.mapStateToProps,
    SearchPageActions.mapDispatchToProps,
);

export default withRedux(withSearch(withDevice(SearchForm)));
