/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import PanelLayout from "@library/components/layouts/PanelLayout";
import Container from "@library/components/layouts/components/Container";
import { withDevice } from "@library/contexts/DeviceContext";
import { IDeviceProps } from "@library/components/DeviceChecker";
import VanillaHeader from "@library/components/headers/VanillaHeader";
import KnowledgeBaseList from "@knowledge/knowledge-bases/KnowledgeBaseList";
import DocumentTitle from "@library/components/DocumentTitle";
import { t } from "@library/application";

export class HomePage extends React.Component<IProps> {
    public render() {
        return (
            <Container>
                <DocumentTitle title={t("Home")}>
                    <VanillaHeader />
                </DocumentTitle>
                <KnowledgeBaseList />
            </Container>
        );
    }
}

interface IProps extends IDeviceProps {}

export default withDevice(HomePage);
