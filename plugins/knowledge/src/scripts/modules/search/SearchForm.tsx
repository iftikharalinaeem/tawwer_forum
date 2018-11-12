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
                <form onSubmit={this.onSubmit}>
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
                </form>
            </Container>
        );
    }

    public componentDidMount() {
        if (window.location.search) {
            const initialForm = qs.parse(window.location.search.replace(/^\?/, ""));

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
    }

    private handleSearchChange = (value: string) => {
        this.props.searchActions.updateForm({ query: value });
    };

    private unwrapResults(): IResult[] {
        const { results } = this.props;
        if (results.data) {
            return results.data.map(this.mapResult);
        } else {
            return [];
        }
    }

    private mapResult(searchResult: ISearchResult): IResult {
        return {
            name: searchResult.name,
            excerpt: searchResult.body,
            meta: <SearchResultMeta updateUser={searchResult.updateUser!} dateUpdated={searchResult.dateUpdated} />,
            url: searchResult.url,
            location: searchResult.knowledgeCategory!.breadcrumbs,
        };
    }

    private loadOptions = (value: string) => {
        const queryObj = {
            name: value,
            expand: ["user", "category"],
        };
        const query = qs.stringify(queryObj);
        return apiv2.get(`/knowledge/search?${query}`).then(results => {
            results = results.data.map(result => {
                return {
                    label: result.name,
                    value: result.name,
                    data: result,
                };
            });
            return results;
        });
    };

    private onSubmit = (event: React.FormEvent) => {
        event.preventDefault();
        void this.props.searchActions.search();
    };
}

const withRedux = connect(
    SearchPageModel.mapStateToProps,
    SearchPageActions.mapDispatchToProps,
);

export default withRedux(withDevice<IProps>(SearchForm));
