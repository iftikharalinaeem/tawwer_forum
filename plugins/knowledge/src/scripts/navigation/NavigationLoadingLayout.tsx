/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import Navigation from "@knowledge/navigation/Navigation";
import { t } from "@library/application";
import FullPageLoader from "@library/components/FullPageLoader";
import VanillaHeader from "@library/components/headers/VanillaHeader";
import Container from "@library/components/layouts/components/Container";
import PanelLayout, { PanelWidget } from "@library/components/layouts/PanelLayout";
import { IActiveRecord } from "@library/components/siteNav/SiteNavNode";
import React from "react";
import Breadcrumbs from "@library/components/Breadcrumbs";

interface IProps {
    activeRecord: IActiveRecord;
}

/**
 * A loading layout that includes navigation items.
 *
 * This is useful when your content loads separately from the navigation.
 * - Provides navigation & breadcrumbs.
 * - Note that hard coded kbID is temporary
 */
export default function NavigationLoadingLayout(props: IProps) {
    return (
        <Container>
            <VanillaHeader
                title={t("Loading")}
                mobileDropDownContent={<Navigation activeRecord={props.activeRecord} collapsible={false} kbID={1} />}
            />
            <PanelLayout
                leftBottom={
                    <PanelWidget>
                        <Navigation activeRecord={props.activeRecord} collapsible={true} kbID={1} />
                    </PanelWidget>
                }
                breadcrumbs={
                    <PanelWidget>
                        <Breadcrumbs forceDisplay={true}>{[]}</Breadcrumbs>
                    </PanelWidget>
                }
                middleBottom={
                    <PanelWidget>
                        <FullPageLoader />
                    </PanelWidget>
                }
                rightBottom={<></>}
            />
        </Container>
    );
}
