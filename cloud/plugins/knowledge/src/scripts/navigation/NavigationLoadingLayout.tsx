/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import Navigation from "@knowledge/navigation/Navigation";
import TitleBar from "@library/headers/TitleBar";
import Container from "@library/layout/components/Container";
import PanelLayout, { PanelWidget } from "@library/layout/PanelLayout";
import { IActiveRecord } from "@library/navigation/SiteNavNode";
import React from "react";
import { useNavHistory } from "@knowledge/navigation/NavHistoryContext";
import { KbViewType } from "@knowledge/knowledge-bases/KnowledgeBaseModel";
import { LoadingRectange, LoadingSpacer } from "@vanilla/library/src/scripts/loaders/LoadingRectangle";
import PageTitle from "@knowledge/modules/common/PageTitle";
import BreadcrumbsLoader from "@vanilla/library/src/scripts/navigation/BreadcrumbsLoader";
import { Devices, useDevice } from "@vanilla/library/src/scripts/layout/DeviceContext";
import { metasClasses } from "@vanilla/library/src/scripts/styles/metasStyles";
import { panelBackgroundVariables } from "@vanilla/library/src/scripts/layout/panelBackgroundStyles";
import { typographyClasses } from "@vanilla/library/src/scripts/styles/typographyStyles";
import ScrollLock from "react-scrolllock";
import { NavigationPlaceholder } from "@knowledge/navigation/NavigationPlaceholder";
import OtherLanguagesPlaceHolder from "@knowledge/modules/article/components/OtherLanguagesPlaceHolder";
import { RelatedArticlesPlaceHolder } from "@knowledge/modules/article/components/RelatedArticlesPlaceholder";
import Banner from "@vanilla/library/src/scripts/banner/Banner";

interface IProps {
    children?: React.ReactNode;
    activeRecord?: IActiveRecord;
    forceLoading?: boolean;
}

/**
 * A loading layout that includes navigation items.
 *
 * This is useful when your content loads separately from the navigation.
 * - Provides navigation & breadcrumbs.
 * - Note that hard coded kbID is temporary
 */
export default function NavigationLoadingLayout(props: IProps) {
    const { lastKB } = useNavHistory();
    const device = useDevice();

    const classesMetas = metasClasses();

    const renderPanelBackground =
        device !== Devices.MOBILE && device !== Devices.XS && panelBackgroundVariables().config.render;

    const navigation =
        !lastKB || !props.activeRecord || props.forceLoading ? (
            <NavigationPlaceholder />
        ) : (
            <Navigation
                activeRecord={props.activeRecord}
                collapsible={lastKB.viewType === KbViewType.GUIDE}
                kbID={lastKB.knowledgeBaseID}
            />
        );

    return (
        <ScrollLock>
            <>
                <TitleBar
                    useMobileBackButton={true}
                    isFixed={true}
                    backgroundColorForMobileDropdown={true} // Will be conditional, based on the settings, but it's true in the sense that it can be colored.
                />
                <Banner
                    isContentBanner
                    backgroundImage={lastKB?.bannerImage}
                    contentImage={lastKB?.bannerContentImage}
                />
                <Container>
                    <PanelLayout
                        renderLeftPanelBackground={renderPanelBackground}
                        breadcrumbs={device !== Devices.MOBILE && device !== Devices.XS && <BreadcrumbsLoader />}
                        leftBottom={<PanelWidget>{navigation}</PanelWidget>}
                        middleTop={
                            <PanelWidget>
                                <PageTitle
                                    title={<LoadingRectange height={24} width={"60%"} />}
                                    headingClassName={typographyClasses().largeTitle}
                                    meta={
                                        <>
                                            <span className={classesMetas.meta}>
                                                <LoadingRectange height={9} width={200} />
                                            </span>
                                        </>
                                    }
                                    includeBackLink={false}
                                />
                            </PanelWidget>
                        }
                        middleBottom={
                            <>
                                <PanelWidget>
                                    <LoadingRectange height={18} />
                                    <LoadingSpacer height={18} />
                                    <LoadingRectange height={14} width={"95%"} />
                                    <LoadingSpacer height={12} />
                                    <LoadingRectange height={14} width={"80%"} />
                                    <LoadingSpacer height={12} />
                                    <LoadingRectange height={14} width={"82%"} />
                                    <LoadingSpacer height={12} />
                                    <LoadingRectange height={14} width={"75%"} />
                                    <LoadingSpacer height={12} />
                                    <LoadingRectange height={14} width={"85%"} />
                                </PanelWidget>
                                <PanelWidget>
                                    <RelatedArticlesPlaceHolder />
                                </PanelWidget>
                            </>
                        }
                        rightTop={
                            <>
                                {device !== Devices.MOBILE && device !== Devices.TABLET && (
                                    <PanelWidget>
                                        <OtherLanguagesPlaceHolder />
                                    </PanelWidget>
                                )}
                            </>
                        }
                    />
                </Container>
            </>
        </ScrollLock>
    );
}
