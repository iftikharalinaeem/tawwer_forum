/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import Container from "@library/components/layouts/components/Container";
import VanillaHeader from "@library/components/headers/VanillaHeader";
import { t } from "@library/application";
import Navigation from "@knowledge/modules/navigation/Navigation";
import { NavigationRecordType } from "@knowledge/@types/api";
import PanelLayout, { PanelWidget } from "@library/components/layouts/PanelLayout";
import { Devices, IDeviceProps } from "@library/components/DeviceChecker";
import FullPageLoader from "@library/components/FullPageLoader";
import { withDevice } from "@library/contexts/DeviceContext";
import NavigationBreadcrumbs from "@knowledge/modules/navigation/NavigationBreadcrumbs";
import { IActiveRecord } from "@library/components/siteNav/SiteNavNode";

interface IProps extends IDeviceProps {
    activeRecord: IActiveRecord;
}

export function NavigationLoadingLayout(props: IProps) {
    return (
        <Container>
            <VanillaHeader
                title={t("Loading")}
                mobileDropDownContent={<Navigation activeRecord={props.activeRecord} collapsible={false} />}
            />
            <PanelLayout device={props.device}>
                <PanelLayout.Breadcrumbs>
                    <PanelWidget>
                        <NavigationBreadcrumbs activeRecord={props.activeRecord} />
                    </PanelWidget>
                </PanelLayout.Breadcrumbs>
                <PanelLayout.LeftBottom>
                    <PanelWidget>
                        <Navigation activeRecord={props.activeRecord} collapsible={true} />
                    </PanelWidget>
                </PanelLayout.LeftBottom>
                <PanelLayout.MiddleBottom>
                    <PanelWidget>
                        <FullPageLoader />
                    </PanelWidget>
                </PanelLayout.MiddleBottom>
                <PanelLayout.RightBottom />
            </PanelLayout>
        </Container>
    );
}

export default withDevice(NavigationLoadingLayout);
