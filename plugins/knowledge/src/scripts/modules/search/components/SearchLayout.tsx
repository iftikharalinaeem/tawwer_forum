/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
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
import { t } from "@library/application";
import AdvancedSearch, { IAdvancedFields, ISearchDomain } from "./AdvancedSearch";
import { dummySearchResults } from "@knowledge/modules/search/state/dummySearchResults";
import { IAttachmentIcon } from "@knowledge/modules/common/AttachmentIcon";
import BigSearch, { IComboBoxOption } from "@library/components/forms/select/BigSearch";
import SearchResults from "@knowledge/modules/common/SearchResults";
import { ISearchWithin } from "../state/dateWithin";

export interface ISearchState extends IAdvancedFields {
    query?: string;
    results?: IResult[];
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

    public constructor(props) {
        super(props);
        this.state = {
            domain: ISearchDomain.ARTICLES,
            title: this.props.title || "",
            query: this.props.query || "",
            author: this.props.author || "",
            fileName: this.props.fileName || "",
            within: this.props.within,
            of: this.props.of || "",
            deletedArticles: !!this.props.deletedArticles,
            results: this.props.results || [],
        };
    }

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

    private setAuthor = (author: IComboBoxOption[]) => {
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
        const { device } = this.props;
        const isMobile = device === Devices.MOBILE;
        const isTablet = device === Devices.TABLET;
        const isFullWidth = device === (Devices.DESKTOP || Devices.NO_BLEED); // This compoment doesn't care about the no bleed, it's the same as desktop

        return (
            <Container>
                <PanelLayout device={this.props.device}>
                    {isFullWidth && <PanelLayout.LeftTop>{}</PanelLayout.LeftTop>}
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
