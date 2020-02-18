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
import { formatUrl, t } from "@library/utility/appUtils";
import classNames from "classnames";
import * as React from "react";
import { ButtonTypes } from "@library/forms/buttonStyles";
import { ComposeIcon } from "@library/icons/common";
import { typographyClasses } from "@library/styles/typographyStyles";
import { KbErrorPage } from "@knowledge/pages/KbErrorPage";
import { DefaultKbError } from "@knowledge/modules/common/KbErrorMessages";

interface IProps {
    results: IResult[];
    query?: string;
    pages?: ILinkPages;
}

export default function FeaturedArticleLayout(props: IProps) {
    const { results, pages } = props;
    const device = useDevice();
    const isFullWidth = [Devices.DESKTOP, Devices.NO_BLEED].includes(device);

    const pageContent =
        results.length > 0 ? (
            <>
                <ResultList results={props.results} />
                <SimplePager url={formatUrl("/kb/articles?page=:page:", true)} pages={pages} />
            </>
        ) : (
            <KbErrorPage className={inheritHeightClass()} defaultError={DefaultKbError.NO_ARTICLES} />
        );

    return (
        <Container>
            <TitleBar />
            <PanelLayout
                leftTop={<React.Fragment />}
                middleTop={
                    <PanelWidget>
                        <PageTitle
                            className="searchBar-heading"
                            headingClassName={typographyClasses().largeTitle}
                            title={t("Featured Articles")}
                            includeBackLink={true}
                        />
                    </PanelWidget>
                }
                middleBottom={pageContent}
                rightTop={isFullWidth && <></>}
            />
        </Container>
    );
}
