/**
 * @author Chris Chabilall <chris.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import PageTitle from "@knowledge/modules/common/PageTitle";
import TitleBar from "@library/headers/TitleBar";
import Container from "@library/layout/components/Container";
import { Devices, useDevice } from "@library/layout/DeviceContext";
import PanelLayout from "@library/layout/PanelLayout";
import SimplePager from "@library/navigation/SimplePager";
import { ILinkPages } from "@library/navigation/SimplePagerModel";
import { IResult } from "@library/result/Result";
import ResultList from "@library/result/ResultList";
import { inheritHeightClass } from "@library/styles/styleHelpers";
import { formatUrl, t } from "@library/utility/appUtils";
import * as React from "react";
import { typographyClasses } from "@library/styles/typographyStyles";
import { KbErrorPage } from "@knowledge/pages/KbErrorPage";
import { DefaultKbError } from "@knowledge/modules/common/KbErrorMessages";
import PanelWidget from "@vanilla/library/src/scripts/layout/components/PanelWidget";
import ThreeColumnLayout from "@vanilla/library/src/scripts/layout/ThreeColumnLayout";

interface IProps {
    title: string;
    results: IResult[];
    query?: string;
    pages: ILinkPages;
}

export default function FeaturedArticleLayout(props: IProps) {
    const { results, pages, title, query } = props;
    const device = useDevice();
    const isFullWidth = [Devices.DESKTOP, Devices.NO_BLEED].includes(device);

    const pageContent =
        results.length > 0 ? (
            <>
                <ResultList results={props.results} />
                <SimplePager url={formatUrl(`/kb/articles?page=:page:${query}`, true)} pages={pages} />
            </>
        ) : (
            <KbErrorPage className={inheritHeightClass()} defaultError={DefaultKbError.NO_ARTICLES} />
        );

    return (
        <Container>
            <TitleBar />
            <ThreeColumnLayout
                leftTop={<React.Fragment />}
                middleTop={
                    <PanelWidget>
                        <PageTitle
                            className="searchBar-heading"
                            headingClassName={typographyClasses().largeTitle}
                            title={title}
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
