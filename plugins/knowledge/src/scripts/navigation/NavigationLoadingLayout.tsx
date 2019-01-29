/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import Navigation from "@knowledge/navigation/Navigation";
import NavigationBreadcrumbs from "@knowledge/navigation/NavigationBreadcrumbs";
import { t } from "@library/application";
import { IDeviceProps } from "@library/components/DeviceChecker";
import FullPageLoader from "@library/components/FullPageLoader";
import VanillaHeader from "@library/components/headers/VanillaHeader";
import Container from "@library/components/layouts/components/Container";
import PanelLayout, { PanelWidget } from "@library/components/layouts/PanelLayout";
import { IActiveRecord } from "@library/components/siteNav/SiteNavNode";
import { withDevice } from "@library/contexts/DeviceContext";
import React from "react";

interface IProps extends IDeviceProps {
    activeRecord: IActiveRecord;
}

/**
 * A loading layout that includes navigation items.
 *
 * This is useful when your content loads separately from the navigation.
 * - Provides navigation & breadcrumbs.
 * - Note that hard coded kbID is temporary
 */
export function NavigationLoadingLayout(props: IProps) {
    return (
        <Container>
            <VanillaHeader
                title={t("Loading")}
                mobileDropDownContent={<Navigation activeRecord={props.activeRecord} collapsible={false} kbID={1} />}
            />
            <PanelLayout
                device={props.device}
                breadcrumbs={
                    <PanelWidget>
                        <NavigationBreadcrumbs activeRecord={props.activeRecord} />
                    </PanelWidget>
                }
                leftBottom={
                    <PanelWidget>
                        <Navigation activeRecord={props.activeRecord} collapsible={true} kbID={1} />
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

export default withDevice(NavigationLoadingLayout);
