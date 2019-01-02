/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import Container from "@library/components/layouts/components/Container";
import { Devices } from "@library/components/DeviceChecker";
import { withDevice } from "@library/contexts/DeviceContext";
import PanelLayout, { PanelWidget, PanelWidgetVerticalPadding } from "@library/components/layouts/PanelLayout";
import { t } from "@library/application";
import PanelEmptyColumn from "@knowledge/modules/search/components/PanelEmptyColumn";
import DraftList from "@knowledge/modules/drafts/components/DraftList";
import DraftHeader from "@knowledge/modules/drafts/components/DraftHeader";
import { IResponseArticleDraft } from "@knowledge/@types/api/article";
import PageTitle from "@knowledge/modules/common/PageTitle";

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
        const isMobile = Devices.MOBILE === device;

        return (
            <React.Fragment>
                <DraftHeader mobileDropDownTitle={isMobile ? t("Drafts") : undefined} />
                <Container>
                    <PanelLayout device={this.props.device} className="hasLargePadding">
                        {isFullWidth && <PanelLayout.LeftTop>{<PanelEmptyColumn />}</PanelLayout.LeftTop>}
                        {!isMobile && (
                            <PanelLayout.MiddleTop>
                                <PageTitle smallPageTitle={true} includeBackLink={false} title={t("Drafts")} />
                            </PanelLayout.MiddleTop>
                        )}
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
