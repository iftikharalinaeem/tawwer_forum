/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import * as React from "react";
import Container from "@knowledge/layouts/components/Container";
import PanelLayout, { PanelWidget, PanelWidgetVerticalPadding } from "@knowledge/layouts/PanelLayout";
import { Devices, IDeviceProps } from "@library/components/DeviceChecker";
import { withDevice } from "@knowledge/contexts/DeviceContext";
import Breadcrumbs, { ICrumb } from "@library/components/Breadcrumbs";
import SearchResults from "@knowledge/modules/common/SearchResults";
import { IResult } from "@knowledge/modules/common/SearchResult";
import { IArticleFragment, IKbCategoryFragment, KbCategoryDisplayType } from "@knowledge/@types/api";
import { dummyArticles } from "@knowledge/modules/categories/state/dummyArticles";
import { SearchResultMeta } from "@knowledge/modules/common/SearchResultMeta";
import { t } from "@library/application";
import SearchBar, { IComboBoxOption } from "@library/components/forms/select/SearchBar";
import { dummySearchResults } from "@knowledge/modules/search/state/dummySearchResults";
import { ButtonBaseClass } from "@library/components/forms/Button";
import { compose } from "@library/components/Icons";
import LinkAsButton from "@library/components/LinkAsButton";

interface IProps extends IDeviceProps {
    breadcrumbData: ICrumb[];
    category: IKbCategoryFragment;
    articles: IArticleFragment[];
    query?: string;
}

interface IState {
    query?: string;
}

export class CategoriesLayout extends React.Component<IProps, IState> {
    public defaultProps = {
        query: "",
    };

    public constructor(props) {
        super(props);
        this.state = {
            query: props.query || "",
        };
    }

    public render() {
        const { category, device } = this.props;
        const options = this.loadSearchSuggestions();
        const isMobile = device === Devices.MOBILE;
        const isTablet = device === Devices.TABLET;
        const isFullWidth = [Devices.DESKTOP, Devices.NO_BLEED].includes(device); // This compoment doesn't care about the no bleed, it's the same as desktop

        return (
            <Container>
                <PanelLayout device={this.props.device}>
                    <PanelLayout.Breadcrumbs>
                        <PanelWidget>
                            <Breadcrumbs>{this.props.breadcrumbData}</Breadcrumbs>
                        </PanelWidget>
                    </PanelLayout.Breadcrumbs>
                    {isFullWidth && <PanelLayout.LeftTop>{}</PanelLayout.LeftTop>}
                    <PanelLayout.MiddleTop>
                        <PanelWidget>
                            <SearchBar
                                placeholder={t("Search")}
                                options={options}
                                setQuery={this.setQuery}
                                query={this.state.query || ""}
                            >
                                {category.name}
                                <LinkAsButton
                                    to={`/kb/articles/add?knowledgeCategoryID=${
                                        this.props.category.knowledgeCategoryID
                                    }`}
                                    className="searchBar-actionButton"
                                    baseClass={ButtonBaseClass.ICON}
                                    title={t("Compose")}
                                >
                                    {compose()}
                                </LinkAsButton>
                            </SearchBar>
                        </PanelWidget>
                    </PanelLayout.MiddleTop>
                    <PanelLayout.MiddleBottom>
                        <PanelWidgetVerticalPadding>
                            <SearchResults results={this.getSearchResults()} />
                        </PanelWidgetVerticalPadding>
                    </PanelLayout.MiddleBottom>
                    {isFullWidth && <PanelLayout.RightTop>{}</PanelLayout.RightTop>}
                </PanelLayout>
            </Container>
        );
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

    private getSearchResults(): IResult[] {
        const { articles } = this.props;
        return articles
            .map(article => {
                return {
                    name: article.name || "",
                    meta: <SearchResultMeta updateUser={article.updateUser} dateUpdated={article.dateUpdated} />,
                    url: article.url,
                    excerpt: article.excerpt || "",
                    attachments: [] as any,
                    dateUpdated: "2018-10-22T16:56:37.423Z",
                    location: [
                        {
                            name: "Article",
                            knowledgeCategoryID: 1,
                            parentID: 1,
                            displayType: KbCategoryDisplayType.HELP,
                            isSection: false,
                            url: "#",
                            dateUpdated: "2018-10-22T16:56:37.423Z",
                            location: [t("Help & Training"), t("Getting Started")],
                        },
                        {
                            name: "Location",
                            knowledgeCategoryID: 1,
                            parentID: 1,
                            displayType: KbCategoryDisplayType.HELP,
                            isSection: false,
                            url: "#",
                            dateUpdated: "2018-10-22T16:56:37.423Z",
                            location: [t("Help & Training"), t("Getting Started")],
                        },
                        {
                            name: "Breadcrumb",
                            knowledgeCategoryID: 1,
                            parentID: 1,
                            displayType: KbCategoryDisplayType.HELP,
                            isSection: false,
                            url: "#",
                            dateUpdated: "2018-10-22T16:56:37.423Z",
                            location: [t("Help & Training"), t("Getting Started")],
                        },
                    ],
                };
            })
            .concat(dummyArticles as any);
    }
}

export default withDevice<IProps>(CategoriesLayout);
