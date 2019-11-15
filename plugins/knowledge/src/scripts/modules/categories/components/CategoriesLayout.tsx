/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IKbCategory } from "@knowledge/@types/api/kbCategory";
import PageTitle from "@knowledge/modules/common/PageTitle";
import Navigation from "@knowledge/navigation/Navigation";
import { KbRecordType } from "@knowledge/navigation/state/NavigationModel";
import { EditorRoute } from "@knowledge/routes/pageRoutes";
import { searchBarClasses } from "@library/features/search/searchBarStyles";
import TitleBar from "@library/headers/TitleBar";
import Container from "@library/layout/components/Container";
import { Devices, IDeviceProps, withDevice } from "@library/layout/DeviceContext";
import PanelLayout, { PanelWidget } from "@library/layout/PanelLayout";
import Breadcrumbs from "@library/navigation/Breadcrumbs";
import SimplePager from "@library/navigation/SimplePager";
import { ILinkPages } from "@library/navigation/SimplePagerModel";
import { IResult } from "@library/result/Result";
import ResultList from "@library/result/ResultList";
import LinkAsButton from "@library/routing/LinkAsButton";
import { inheritHeightClass } from "@library/styles/styleHelpers";
import { t } from "@library/utility/appUtils";
import classNames from "classnames";
import * as React from "react";
import PageErrorMessage, { DefaultError } from "@knowledge/modules/common/PageErrorMessage";
import { ButtonTypes } from "@library/forms/buttonStyles";
import { ComposeIcon } from "@library/icons/common";

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
                <>
                    <ResultList results={this.props.results} />
                    <SimplePager url={category.url + "/p:page:"} pages={pages} />
                </>
            ) : (
                <PageErrorMessage
                    className={inheritHeightClass()}
                    defaultError={DefaultError.CATEGORY_NO_ARTICLES}
                    knowledgeBaseID={category.knowledgeBaseID}
                    knowledgeCategoryID={category.knowledgeCategoryID}
                />
            );

        return (
            <Container>
                <TitleBar
                    useMobileBackButton={true}
                    title={category.name}
                    mobileDropDownContent={
                        <Navigation collapsible={false} activeRecord={activeRecord} kbID={category.knowledgeBaseID} />
                    }
                />
                <PanelLayout
                    device={this.props.device}
                    breadcrumbs={
                        category.breadcrumbs && <Breadcrumbs forceDisplay={false}>{category.breadcrumbs}</Breadcrumbs>
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
                                className="searchBar-heading"
                                title={category.name}
                                actions={
                                    <LinkAsButton
                                        to={EditorRoute.url(category)}
                                        onMouseOver={EditorRoute.preload}
                                        className={classNames("searchBar-actionButton", classesSearchBar.actionButton)}
                                        baseClass={ButtonTypes.ICON_COMPACT}
                                        title={t("Compose")}
                                    >
                                        <ComposeIcon />
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
