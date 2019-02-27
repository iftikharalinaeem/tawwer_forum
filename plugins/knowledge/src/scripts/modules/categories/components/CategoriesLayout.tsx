/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IKbCategory } from "@knowledge/@types/api";
import PageTitle from "@knowledge/modules/common/PageTitle";
import { IResult } from "@knowledge/modules/common/SearchResult";
import SearchResults from "@knowledge/modules/common/SearchResults";
import Navigation from "@knowledge/navigation/Navigation";
import { KbRecordType } from "@knowledge/navigation/state/NavigationModel";
import ErrorPage, { DefaultError } from "@knowledge/routes/ErrorPage";
import { EditorRoute } from "@knowledge/routes/pageRoutes";
import { t } from "@library/application";
import Breadcrumbs from "@library/components/Breadcrumbs";
import { Devices, IDeviceProps } from "@library/components/DeviceChecker";
import { ButtonBaseClass } from "@library/components/forms/Button";
import VanillaHeader from "@library/components/headers/VanillaHeader";
import { compose } from "@library/components/icons";
import Container from "@library/components/layouts/components/Container";
import PanelLayout, { PanelWidget, PanelWidgetVerticalPadding } from "@library/components/layouts/PanelLayout";
import LinkAsButton from "@library/components/LinkAsButton";
import { withDevice } from "@library/contexts/DeviceContext";
import SimplePager from "@library/simplePager/SimplePager";
import { ILinkPages } from "@library/simplePager/SimplePagerModel";
import { searchBarClasses } from "@library/styles/searchBarStyles";
import classNames from "classnames";
import * as React from "react";

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
        const { category, device, pages, results } = this.props;
        const activeRecord = {
            recordType: KbRecordType.CATEGORY,
            recordID: category.knowledgeCategoryID,
        };
        const isFullWidth = [Devices.DESKTOP, Devices.NO_BLEED].includes(device); // This compoment doesn't care about the no bleed, it's the same as desktop
        const classesSearchBar = searchBarClasses();

        const pageContent =
            results.length > 0 ? (
                <PanelWidgetVerticalPadding>
                    <SearchResults results={this.props.results} />
                    <SimplePager url={category.url + "/p:page:"} pages={pages} />
                </PanelWidgetVerticalPadding>
            ) : (
                <ErrorPage
                    defaultError={DefaultError.CATEGORY_NO_ARTICLES}
                    knowledgeBaseID={category.knowledgeBaseID}
                    knowledgeCategoryID={category.knowledgeCategoryID}
                />
            );

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
                        category.breadcrumbs && (
                            <PanelWidget>
                                <Breadcrumbs children={category.breadcrumbs} forceDisplay={false} />
                            </PanelWidget>
                        )
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
                                        className={classNames("searchBar-actionButton", classesSearchBar.actionButton)}
                                        baseClass={ButtonBaseClass.ICON}
                                        title={t("Compose")}
                                    >
                                        {compose()}
                                    </LinkAsButton>
                                }
                            >
                                <label className={classNames("searchBar-label", classesSearchBar.label)}>
                                    {category.name}
                                </label>
                            </PageTitle>
                        </PanelWidget>
                    }
                    middleBottom={pageContent}
                    rightTop={isFullWidth && <></>}
                />
            </Container>
        );
    }
}

export default withDevice<IProps>(CategoriesLayout);
