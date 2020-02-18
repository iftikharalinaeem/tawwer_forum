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
import { Devices, useDevice } from "@library/layout/DeviceContext";
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
import { ButtonTypes } from "@library/forms/buttonStyles";
import { ComposeIcon } from "@library/icons/common";
import { typographyClasses } from "@library/styles/typographyStyles";
import { KbErrorPage } from "@knowledge/pages/KbErrorPage";
import { DefaultKbError } from "@knowledge/modules/common/KbErrorMessages";

interface IProps {
    category: IKbCategory;
    results: IResult[];
    query?: string;
    pages: ILinkPages;
    useBackButton?: boolean;
}

//rename to articlelist
export default function CategoriesLayout(props: IProps) {
    const { category, pages, results } = props;
    const activeRecord = {
        recordType: KbRecordType.CATEGORY,
        recordID: category.knowledgeCategoryID,
    };
    const device = useDevice();
    const isFullWidth = [Devices.DESKTOP, Devices.NO_BLEED].includes(device); // This compoment doesn't care about the no bleed, it's the same as desktop
    const classesSearchBar = searchBarClasses();
    const crumbs = category.breadcrumbs;
    const lastCrumb = crumbs && crumbs.length > 1 ? crumbs.slice(t.length - 1) : crumbs;

    const pageContent =
        results.length > 0 ? (
            <>
                <ResultList results={props.results} />
                <SimplePager url={category.url + "/p:page:"} pages={pages} />
            </>
        ) : (
            <KbErrorPage
                className={inheritHeightClass()}
                defaultError={DefaultKbError.CATEGORY_NO_ARTICLES}
                knowledgeBaseID={category.knowledgeBaseID}
                knowledgeCategoryID={category.knowledgeCategoryID}
            />
        );

    return (
        <Container>
            <TitleBar
                extraBurgerNavigation={
                    <Navigation
                        inHamburger
                        collapsible={true}
                        activeRecord={activeRecord}
                        kbID={category.knowledgeBaseID}
                    />
                }
                useMobileBackButton={true}
            />
            <PanelLayout
                breadcrumbs={
                    (device === Devices.XS || device === Devices.MOBILE) && category.breadcrumbs
                        ? lastCrumb && <Breadcrumbs forceDisplay={false}>{lastCrumb}</Breadcrumbs>
                        : category.breadcrumbs && <Breadcrumbs forceDisplay={false}>{category.breadcrumbs}</Breadcrumbs>
                }
                leftBottom={
                    <PanelWidget>
                        <Navigation collapsible={true} activeRecord={activeRecord} kbID={category.knowledgeBaseID} />
                    </PanelWidget>
                }
                middleTop={
                    <PanelWidget>
                        <PageTitle
                            className="searchBar-heading"
                            headingClassName={typographyClasses().largeTitle}
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
                            includeBackLink={device !== Devices.MOBILE && device !== Devices.XS && props.useBackButton}
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
