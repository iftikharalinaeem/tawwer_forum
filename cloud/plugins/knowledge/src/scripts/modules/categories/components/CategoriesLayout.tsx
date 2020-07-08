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
import PanelLayout from "@library/layout/PanelLayout";
import Breadcrumbs from "@library/navigation/Breadcrumbs";
import SimplePager from "@library/navigation/SimplePager";
import { ILinkPages } from "@library/navigation/SimplePagerModel";
import { IResult } from "@library/result/Result";
import ResultList from "@library/result/ResultList";
import LinkAsButton from "@library/routing/LinkAsButton";
import { t } from "@library/utility/appUtils";
import classNames from "classnames";
import * as React from "react";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { ComposeIcon } from "@library/icons/common";
import { typographyClasses } from "@library/styles/typographyStyles";
import KbErrorMessages, { DefaultKbError } from "@knowledge/modules/common/KbErrorMessages";
import { KbPermission } from "@knowledge/knowledge-bases/KbPermission";
import { useLayout } from "@library/layout/LayoutContext";
import PanelWidget from "@vanilla/library/src/scripts/layout/components/PanelWidget";
import ThreeColumnLayout from "@vanilla/library/src/scripts/layout/ThreeColumnLayout";

interface IProps {
    category: IKbCategory;
    results: IResult[];
    query?: string;
    pages: ILinkPages;
    useBackButton?: boolean;
}

export default function CategoriesLayout(props: IProps) {
    const { category, pages, results } = props;
    const activeRecord = {
        recordType: KbRecordType.CATEGORY,
        recordID: category.knowledgeCategoryID,
    };
    const { isCompact, isFullWidth } = useLayout();

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
            <KbErrorMessages
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
            <ThreeColumnLayout
                breadcrumbs={
                    isCompact && category.breadcrumbs
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
                                <KbPermission permission="articles.add" kbID={category.knowledgeBaseID}>
                                    <LinkAsButton
                                        to={EditorRoute.url(category)}
                                        onMouseOver={EditorRoute.preload}
                                        className={classNames("searchBar-actionButton", classesSearchBar.actionButton)}
                                        baseClass={ButtonTypes.ICON_COMPACT}
                                        title={t("Compose")}
                                    >
                                        <ComposeIcon />
                                    </LinkAsButton>
                                </KbPermission>
                            }
                            includeBackLink={!isCompact && props.useBackButton}
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
