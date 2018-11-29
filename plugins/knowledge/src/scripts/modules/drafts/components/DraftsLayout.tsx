/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import Container from "@library/components/layouts/components/Container";
import { Devices } from "@library/components/DeviceChecker";
import { withDevice } from "@library/contexts/DeviceContext";
import PanelLayout, { PanelWidget, PanelWidgetVerticalPadding } from "@library/components/layouts/PanelLayout";
import { t } from "@library/application";
import PanelEmptyColumn from "@knowledge/modules/search/components/PanelEmptyColumn";
import { PageTitle } from "@knowledge/modules/common/PageTitle";
import DraftList from "@knowledge/modules/drafts/components/DraftList";
import DraftHeader from "@knowledge/modules/drafts/components/DraftHeader";
import { IResponseArticleDraft } from "@knowledge/@types/api/article";

interface IProps {
    device: Devices;
    data: IResponseArticleDraft[];
}

/*
 * Implements Draft layout
 */
class DraftsLayout extends React.Component<IProps> {
    public render() {
        const { device } = this.props;
        const isFullWidth = [Devices.DESKTOP, Devices.NO_BLEED].includes(device); // This compoment doesn't care about the no bleed, it's the same as desktop

        return (
            <React.Fragment>
                <DraftHeader />
                <Container>
                    <PanelLayout device={this.props.device}>
                        {isFullWidth && <PanelLayout.LeftTop>{<PanelEmptyColumn />}</PanelLayout.LeftTop>}
                        <PanelLayout.MiddleTop>
                            <PanelWidget>
                                <PageTitle title={t("Drafts")} device={device} backUrl={null} />
                            </PanelWidget>
                        </PanelLayout.MiddleTop>
                        <PanelLayout.MiddleBottom>
                            <PanelWidgetVerticalPadding>
                                <DraftList data={this.props.data} />
                            </PanelWidgetVerticalPadding>
                        </PanelLayout.MiddleBottom>
                        {isFullWidth && <PanelLayout.RightTop>{<PanelEmptyColumn />}</PanelLayout.RightTop>}
                    </PanelLayout>
                </Container>
            </React.Fragment>
        );
    }
}

export default withDevice<IProps>(DraftsLayout);
