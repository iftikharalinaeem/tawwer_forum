/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IKbCategoryFragment, NavigationRecordType } from "@knowledge/@types/api";
import { IResult } from "@knowledge/modules/common/SearchResult";
import SearchResults from "@knowledge/modules/common/SearchResults";
import Navigation from "@knowledge/modules/navigation/Navigation";
import NavigationBreadcrumbs from "@knowledge/modules/navigation/NavigationBreadcrumbs";
import { EditorRoute } from "@knowledge/routes/pageRoutes";
import { t } from "@library/application";
import { Devices, IDeviceProps } from "@library/components/DeviceChecker";
import { ButtonBaseClass } from "@library/components/forms/Button";
import SearchBar from "@library/components/forms/select/SearchBar";
import VanillaHeader from "@library/components/headers/VanillaHeader";
import { compose } from "@library/components/icons/common";
import Container from "@library/components/layouts/components/Container";
import PanelLayout, { PanelWidget, PanelWidgetVerticalPadding } from "@library/components/layouts/PanelLayout";
import LinkAsButton from "@library/components/LinkAsButton";
import { withDevice } from "@library/contexts/DeviceContext";
import * as React from "react";

interface IProps extends IDeviceProps {
    category: IKbCategoryFragment;
    results: IResult[];
    query?: string;
    kbID: number;
}

interface IState {
    query?: string;
}

export class CategoriesLayout extends React.Component<IProps, IState> {
    public state: IState = {
        query: this.props.query || "",
    };

    public render() {
        const { category, device } = this.props;
        const activeRecord = {
            recordType: NavigationRecordType.KNOWLEDGE_CATEGORY,
            recordID: category.knowledgeCategoryID,
        };
        const isFullWidth = [Devices.DESKTOP, Devices.NO_BLEED].includes(device); // This compoment doesn't care about the no bleed, it's the same as desktop

        return (
            <Container>
                <VanillaHeader
                    title={category.name}
                    mobileDropDownContent={
                        <Navigation collapsible={false} activeRecord={activeRecord} kbID={this.props.kbID} />
                    }
                />
                <PanelLayout device={this.props.device}>
                    <PanelLayout.Breadcrumbs>
                        <PanelWidget>
                            <NavigationBreadcrumbs activeRecord={activeRecord} />
                        </PanelWidget>
                    </PanelLayout.Breadcrumbs>
                    <PanelLayout.LeftBottom>
                        <PanelWidget>
                            {
                                <Navigation
                                    collapsible={true}
                                    activeRecord={activeRecord}
                                    kbID={1}
                                    title={t("Subcategories")}
                                />
                            }
                        </PanelWidget>
                    </PanelLayout.LeftBottom>
                    <PanelLayout.MiddleTop>
                        <PanelWidget>
                            <SearchBar
                                placeholder={t("Search")}
                                onChange={this.setQuery}
                                value={this.state.query || ""}
                                title={category.name}
                                titleAsComponent={
                                    <>
                                        {category.name}
                                        <LinkAsButton
                                            to={EditorRoute.url(category)}
                                            onMouseOver={EditorRoute.preload}
                                            className="searchBar-actionButton"
                                            baseClass={ButtonBaseClass.ICON}
                                            title={t("Compose")}
                                        >
                                            {compose()}
                                        </LinkAsButton>
                                    </>
                                }
                            />
                        </PanelWidget>
                    </PanelLayout.MiddleTop>
                    <PanelLayout.MiddleBottom>
                        <PanelWidgetVerticalPadding>
                            <SearchResults results={this.props.results} />
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
}

export default withDevice<IProps>(CategoriesLayout);
