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
import { KbRecordType } from "@knowledge/navigation/state/NavigationModel";
import { Devices, IDeviceProps } from "@library/components/DeviceChecker";
import VanillaHeader from "@library/components/headers/VanillaHeader";
import Heading from "@library/components/Heading";
import Container from "@library/components/layouts/components/Container";
import PanelLayout, { PanelWidget, PanelWidgetVerticalPadding } from "@library/components/layouts/PanelLayout";
import { withDevice } from "@library/contexts/DeviceContext";
import SimplePager from "@library/simplePager/SimplePager";
import { ILinkPages } from "@library/simplePager/SimplePagerModel";
import * as React from "react";
import PageTitle from "@knowledge/modules/common/PageTitle";
import LinkAsButton from "@library/components/LinkAsButton";
import { EditorRoute } from "@knowledge/routes/pageRoutes";
import { ButtonBaseClass } from "@library/components/forms/Button";
import { t } from "@library/application";
import { compose } from "@library/components/icons";

interface IProps extends IDeviceProps {
    category: IKbCategory;
    results: IResult[];
    query?: string;
    pages: ILinkPages;
}

interface IState {
    query?: string;
}

export class CategoriesLayout extends React.Component<IProps, IState> {
    public state: IState = {
        query: this.props.query || "",
    };

    public render() {
        const { category, device, pages } = this.props;
        const activeRecord = {
            recordType: KbRecordType.CATEGORY,
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
                            <PageTitle
                                className="searchBar-heading pageSmallTitle"
                                title={category.name}
                                actions={
                                    <LinkAsButton
                                        to={EditorRoute.url(category)}
                                        onMouseOver={EditorRoute.preload}
                                        className="searchBar-actionButton"
                                        baseClass={ButtonBaseClass.ICON}
                                        title={t("Compose")}
                                    >
                                        {compose()}
                                    </LinkAsButton>
                                }
                            >
                                <label className="searchBar-label">{category.name}</label>
                            </PageTitle>
                        </PanelWidget>
                    }
                    middleBottom={
                        <PanelWidgetVerticalPadding>
                            <SearchResults results={this.props.results} />
                            <SimplePager url={category.url + "/p:page:"} pages={pages} />
                        </PanelWidgetVerticalPadding>
                    }
                    rightTop={isFullWidth && <></>}
                />
            </Container>
        );
    }
}

export default withDevice<IProps>(CategoriesLayout);
