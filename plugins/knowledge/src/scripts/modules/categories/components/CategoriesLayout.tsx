/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IKbCategory } from "@knowledge/@types/api";
import { IResult } from "@knowledge/modules/common/SearchResult";
import SearchResults from "@knowledge/modules/common/SearchResults";
import Navigation from "@knowledge/navigation/Navigation";
import NavigationBreadcrumbs from "@knowledge/navigation/NavigationBreadcrumbs";
import { NavigationRecordType } from "@knowledge/navigation/state/NavigationModel";
import { Devices, IDeviceProps } from "@library/components/DeviceChecker";
import VanillaHeader from "@library/components/headers/VanillaHeader";
import Heading from "@library/components/Heading";
import Container from "@library/components/layouts/components/Container";
import PanelLayout, { PanelWidget, PanelWidgetVerticalPadding } from "@library/components/layouts/PanelLayout";
import { withDevice } from "@library/contexts/DeviceContext";
import * as React from "react";

interface IProps extends IDeviceProps {
    category: IKbCategory;
    results: IResult[];
    query?: string;
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
                        <Navigation collapsible={false} activeRecord={activeRecord} kbID={category.knowledgeBaseID} />
                    }
                />
                <PanelLayout
                    device={this.props.device}
                    breadcrumbs={
                        <PanelWidget>
                            <NavigationBreadcrumbs activeRecord={activeRecord} />
                        </PanelWidget>
                    }
                    leftBottom={
                        <PanelWidget>
                            <Navigation
                                collapsible={true}
                                activeRecord={activeRecord}
                                kbID={category.knowledgeBaseID}
                            />
                        </PanelWidget>
                    }
                    middleTop={
                        <PanelWidget>
                            <Heading depth={1} className="searchBar-heading pageSmallTitle" title={category.name}>
                                <label className="searchBar-label">{category.name}</label>
                            </Heading>
                        </PanelWidget>
                    }
                    middleBottom={
                        <PanelWidgetVerticalPadding>
                            <SearchResults results={this.props.results} />
                        </PanelWidgetVerticalPadding>
                    }
                    rightTop={isFullWidth && <></>}
                />
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
