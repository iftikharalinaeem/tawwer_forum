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
import AdvancedSearch, { IAdvancedFields, ISearchDomain, ISearchWithin } from "./AdvancedSearch";
import { dummySearchResults } from "@knowledge/modules/search/state/dummySearchResults";
import { IAttachmentIcon } from "@knowledge/modules/common/AttachmentIcon";
import BigSearch, { IComboBoxOption } from "@library/components/forms/select/BigSearch";
import InputTextBlock from "@dashboard/components/forms/InputTextBlock";

export interface ISearchState extends IAdvancedFields {
    query: string;
    results: IResult[];
    autoComplete: boolean;
}

interface IProps extends ISearchState {
    placeholder?: string;
    device: Devices;
    deletedArticles: boolean;
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
            domain: ISearchDomain.ARTICLES,
            title: "My Title",
            author: ["Todd", "Dan", "Mister Clean"], // TBD in next PR
            fileName: "My File Name",
            within: ISearchWithin.ONE_DAY,
            of: "Monday",
            deletedArticles: false,
        };
    }

    private setDomain = (domain: ISearchDomain) => {
        this.setState({
            domain,
        });
    };

    private setTitle = (title: string) => {
        this.setState({
            title,
        });
    };

    private setAuthor = (author: string[]) => {
        this.setState({
            author,
        });
    };

    private setFileName = (fileName: string) => {
        this.setState({
            fileName,
        });
    };

    private setWithin = (within: ISearchWithin) => {
        this.setState({
            within,
        });
    };

    private setOf = (of: string) => {
        this.setState({
            of,
        });
    };
    private setDeletedArticles = (deletedArticles: boolean) => {
        this.setState({
            deletedArticles,
        });
    };

    public render() {
        const options = this.loadSearchSuggestions();
        return (
            <Container>
                <PanelLayout device={this.props.device}>
                    <PanelLayout.MiddleTop>
                        <BigSearch
                            placeholder={this.props.placeholder || t("Help")}
                            options={options}
                            setQuery={this.setQuery}
                            query={this.state.query || ""}
                        />
                    </PanelLayout.MiddleTop>
                    <PanelLayout.MiddleBottom>
                        {<SearchResults results={dummySearchResults} />}
                    </PanelLayout.MiddleBottom>
                    <PanelLayout.RightTop>
                        <AdvancedSearch
                            domain={this.state.domain}
                            setDomain={this.setDomain}
                            title={this.state.title}
                            setTitle={this.setTitle}
                            setAuthor={this.setAuthor}
                            author={this.state.author}
                            setFileName={this.setFileName}
                            fileName={this.state.fileName}
                            setWithin={this.setWithin}
                            within={this.state.within}
                            setOf={this.setOf}
                            of={this.state.of}
                            setDeletedArticles={this.setDeletedArticles}
                            deletedArticles={!!this.state.deletedArticles}
                        />
                    </PanelLayout.RightTop>
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
