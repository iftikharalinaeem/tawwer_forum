/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import Container from "@library/layout/components/Container";
import { withDevice, Devices } from "@library/layout/DeviceContext";
import { t } from "@library/utility/appUtils";
import DraftList from "@knowledge/modules/drafts/components/DraftList";
import DraftHeader from "@knowledge/modules/drafts/components/DraftHeader";
import { IResponseArticleDraft } from "@knowledge/@types/api/article";
import PageTitle from "@knowledge/modules/common/PageTitle";
import PanelLayout from "@vanilla/library/src/scripts/layout/PanelLayout";

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
        const isMobile = Devices.MOBILE === device || Devices.XS === device;

        return (
            <React.Fragment>
                <DraftHeader mobileDropDownTitle={isMobile ? t("Drafts") : undefined} />
                <Container>
                    <PanelLayout
                        className="hasLargePadding"
                        leftTop={isFullWidth && <React.Fragment />}
                        middleTop={!isMobile && <PageTitle includeBackLink={false} title={t("Drafts")} />}
                        middleBottom={<DraftList data={this.props.data} />}
                        rightTop={isFullWidth && <React.Fragment />}
                    />
                </Container>
            </React.Fragment>
        );
    }
}

export default withDevice<IProps>(DraftsLayout);
