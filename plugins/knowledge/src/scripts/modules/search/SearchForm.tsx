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
import { dummySearchResults } from "@knowledge/modules/search/state/dummySearchResults";
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

interface IProps extends ISearchFormActionProps, ISearchPageState {
    placeholder?: string;
    device: Devices;
}

class SearchForm extends React.Component<IProps> {
    public render() {
        const options = this.loadSearchSuggestions();
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
                                    options={options}
                                    onChange={(value: any) => {
                                        this.props.searchActions.updateForm({ query: value });
                                    }}
                                    loadOptions={this.loadOptions}
                                    value={this.props.form.query}
                                    isBigInput={true}
                                    onSearch={this.queueSearch}
                                    isLoading={this.props.results.status === LoadStatus.LOADING}
                                />
                            </PanelWidget>
                        </PanelLayout.MiddleTop>
                        <PanelLayout.MiddleBottom>
                            <PanelWidgetVerticalPadding>
                                {<SearchResults results={this.props.results} />}
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

    private queueSearch = () => {
        this.props.searchActions.search();
    };

    public componentDidMount() {
        if (window.location.search) {
            const initialForm = qs.parse(window.location.search.replace(/^\?/, ""));

            if ("authors" in initialForm) {
                initialForm.authors.map(option => {
                    option.value = Number.parseInt(option.value, 10);
                });
            }

            this.props.searchActions.updateForm(initialForm);
            this.props.searchActions.search();
        }
    }

    private loadOptions = (value: string) => {
        return apiv2.get(`/knowledge/search?name=${value}`).then(results => {
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

    /**
     * Load dummy data
     */
    public loadSearchSuggestions = () => {
        const data = dummySearchResults.map((result, index) => {
            return {
                label: result.name,
                value: index.toString(),
                ...result,
            };
        });
        return data || [];
    };

    private onSubmit = (event: React.FormEvent) => {
        event.preventDefault();
        this.props.searchActions.search();
    };
}

const withRedux = connect(
    SearchPageModel.mapStateToProps,
    SearchPageActions.mapDispatchToProps,
);

export default withRedux(withDevice<IProps>(SearchForm));
