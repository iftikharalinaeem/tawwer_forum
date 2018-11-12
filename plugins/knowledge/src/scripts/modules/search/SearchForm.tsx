/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import * as React from "react";
import Container from "@knowledge/layouts/components/Container";
import { Devices } from "@library/components/DeviceChecker";
import { withDevice } from "@knowledge/contexts/DeviceContext";
import PanelLayout, { PanelWidget, PanelWidgetVerticalPadding } from "@knowledge/layouts/PanelLayout";
import { t } from "@library/application";
import AdvancedSearch from "@knowledge/modules/search/components/AdvancedSearch";
import SearchBar from "@library/components/forms/select/SearchBar";
import SearchResults from "@knowledge/modules/common/SearchResults";
import PanelEmptyColumn from "@knowledge/modules/search/components/PanelEmptyColumn";
import { connect } from "react-redux";
import SearchPageModel, { ISearchPageState } from "@knowledge/modules/search/SearchPageModel";
import SearchPageActions, { ISearchFormActionProps } from "@knowledge/modules/search/SearchPageActions";
import QueryString from "@library/components/navigation/QueryString";
import apiv2 from "@library/apiv2";
import qs from "qs";
import { LoadStatus } from "@library/@types/api";
import { IResult } from "@knowledge/modules/common/SearchResult";
import { ISearchResult } from "@knowledge/@types/api";
import { SearchResultMeta } from "@knowledge/modules/common/SearchResultMeta";
import SearchOption from "@knowledge/modules/search/components/SearchOption";

interface IProps extends ISearchFormActionProps, ISearchPageState {
    placeholder?: string;
    device: Devices;
}

class SearchForm extends React.Component<IProps> {
    public render() {
        const { device } = this.props;
        const isMobile = device === Devices.MOBILE;
        const isTablet = device === Devices.TABLET;
        const isFullWidth = [Devices.DESKTOP, Devices.NO_BLEED].includes(device); // This compoment doesn't care about the no bleed, it's the same as desktop

        return (
            <Container>
                <QueryString value={this.props.form} />
                <PanelLayout device={this.props.device}>
                    {isFullWidth && <PanelLayout.LeftTop>{<PanelEmptyColumn />}</PanelLayout.LeftTop>}
                    <PanelLayout.MiddleTop>
                        <PanelWidget>
                            <SearchBar
                                placeholder={this.props.placeholder || t("Help")}
                                onChange={this.handleSearchChange}
                                loadOptions={this.loadOptions}
                                value={this.props.form.query}
                                isBigInput={true}
                                onSearch={this.props.searchActions.search}
                                isLoading={this.props.results.status === LoadStatus.LOADING}
                                optionComponent={SearchOption}
                            />
                        </PanelWidget>
                    </PanelLayout.MiddleTop>
                    <PanelLayout.MiddleBottom>
                        <PanelWidgetVerticalPadding>
                            {<SearchResults results={this.unwrapResults()} />}
                        </PanelWidgetVerticalPadding>
                    </PanelLayout.MiddleBottom>
                    <PanelLayout.RightTop>
                        <PanelWidget>
                            <AdvancedSearch />
                        </PanelWidget>
                    </PanelLayout.RightTop>
                </PanelLayout>
            </Container>
        );
    }

    /**
     * @inheritdoc
     */
    public componentDidMount() {
        if (window.location.search) {
            this.initializeFromQueryString(window.location.search);
        }
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

    /**
     * Unwrap loaded results and map them into the proper shape.
     */
    private unwrapResults(): IResult[] {
        const { results } = this.props;
        if (results.data) {
            return results.data.map(this.mapResult);
        } else {
            return [];
        }
    }

    /**
     * Map a search API response into what the <SearchResults /> component is expecting.
     *
     * @param searchResult The API search result to map.
     */
    private mapResult(searchResult: ISearchResult): IResult {
        return {
            name: searchResult.name,
            excerpt: searchResult.body,
            meta: <SearchResultMeta updateUser={searchResult.updateUser!} dateUpdated={searchResult.dateUpdated} />,
            url: searchResult.url,
            location: searchResult.knowledgeCategory!.breadcrumbs,
        };
    }

    /**
     * Simple data loading function for the search bar/react-select.
     */
    private loadOptions = async (value: string) => {
        const queryObj = {
            name: value,
            expand: ["user", "category"],
        };
        const query = qs.stringify(queryObj);
        const response = await apiv2.get(`/knowledge/search?${query}`);
        return response.data.map(result => {
            return {
                label: result.name,
                value: result.name,
                data: result,
            };
        });
    };
}

const withRedux = connect(
    SearchPageModel.mapStateToProps,
    SearchPageActions.mapDispatchToProps,
);

export default withRedux(withDevice<IProps>(SearchForm));
