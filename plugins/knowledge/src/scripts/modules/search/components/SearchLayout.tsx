/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import Container from "@knowledge/layouts/components/Container";
import { Devices, IDeviceProps } from "@library/components/DeviceChecker";
import { withDevice } from "@knowledge/contexts/DeviceContext";
import { IResult } from "@knowledge/modules/common/SearchResult";
import { IArticleFragment, IKbCategoryFragment } from "@knowledge/@types/api";
import { dummyArticles } from "@knowledge/modules/categories/state/dummyArticles";
import { SearchResultMeta } from "@knowledge/modules/common/SearchResultMeta";
import PanelLayout, { PanelWidget } from "@knowledge/layouts/PanelLayout";
import PageTitle from "@knowledge/modules/common/PageTitle";
import SearchResults from "@knowledge/modules/common/SearchResults";
import { t } from "@library/application";
import AdvancedSearch from "./AdvancedSearch";
import { dummySearchResults } from "@knowledge/modules/search/state/dummySearchResults";
import { IAttachmentIcon } from "@knowledge/modules/common/AttachmentIcon";
import BigSearch, { IComboBoxOption } from "@library/components/forms/select/BigSearch";
import InputTextBlock from "@dashboard/components/forms/InputTextBlock";

export enum ISearchDomain {
    ARTICLES = "articles",
    EVERYWHERE = "everywhere",
}

export enum ISearchWithin {
    ONE_DAY = "1 day",
    THREE_DAY = "3 days",
    ONE_WEEK = "1 week",
    TWO_WEEKS = "2 weeks",
    ONE_MONTH = "1 month",
    SIX_MONTHS = "6 months",
    ONE_YEAR = "1 year",
}

export interface IAdvancedFields {
    domain: ISearchDomain;
    title: string;
    author: any[]; // TBD in next PR
    fileName: string;
    within: ISearchWithin;
    of: string;
    deletedArticles: boolean;
}

export interface ISearchState {
    query: string;
    results: IResult[];
    autoComplete: boolean;
    advanced: IAdvancedFields;
}

interface IProps extends ISearchState {
    placeholder?: string;
    device: Devices;
}

class SearchLayout extends React.Component<IProps, ISearchState> {
    private clearQuery = () => {
        this.setState({
            query: "",
        });
    };

    private setQuery = value => {
        let newValue = "";
        if (typeof value === "string") {
            newValue = value;
        } else if (value.data) {
            newValue = value.data;
        }
        this.setState({
            query: newValue,
        });
    };

    constructor(props: IProps) {
        super(props);
        // Hard coded state for now, until it's hooked up to redux
        this.state = {
            query: "My query",
            results: dummySearchResults,
            autoComplete: false,
            advanced: {
                domain: ISearchDomain.ARTICLES,
                title: "My Title",
                author: ["Todd", "Dan", "Mister Clean"], // TBD in next PR
                fileName: "My File Name",
                within: ISearchWithin.ONE_DAY,
                of: "Monday",
                deletedArticles: false,
            },
        };
    }

    public doNothing() {
        return;
    }

    public render() {
        const options = this.loadSearchSuggestions();
        return (
            <Container>
                <PanelLayout device={this.props.device}>
                    <PanelLayout.MiddleTop>
                        <BigSearch
                            // query={this.state.query}
                            placeholder={this.props.placeholder || t("Help")}
                            // clearQuery={this.clearQuery}
                            // setSearchQuery={this.setQuery}
                            options={options}
                            setQuery={this.setQuery}
                            query={this.state.query || ""}
                        />
                    </PanelLayout.MiddleTop>
                    {/*<PanelLayout.MiddleBottom>*/}
                    {/*<SearchResults results={this.state.results} />*/}
                    {/*</PanelLayout.MiddleBottom>*/}
                    {/*<PanelLayout.RightTop>*/}
                    {/*<AdvancedSearch />*/}
                    {/*</PanelLayout.RightTop>*/}
                </PanelLayout>
            </Container>
        );
    }

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
}

export default withDevice<IProps>(SearchLayout);
