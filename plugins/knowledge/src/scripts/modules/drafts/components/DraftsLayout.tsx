/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import Container from "@knowledge/layouts/components/Container";
import { Devices, IDeviceProps } from "@library/components/DeviceChecker";
import { withDevice } from "@knowledge/contexts/DeviceContext";
import { IResult } from "@knowledge/modules/common/SearchResult";
import { IArticleFragment, IKbCategoryFragment } from "@knowledge/@types/api";
import { dummyArticles } from "@knowledge/modules/categories/state/dummyArticles";
import { SearchResultMeta } from "@knowledge/modules/common/SearchResultMeta";
import PanelLayout, { PanelWidget, PanelWidgetVerticalPadding } from "@knowledge/layouts/PanelLayout";
import { t } from "@library/application";
import { dummySearchResults } from "@knowledge/modules/search/state/dummySearchResults";
import { IAttachmentIcon } from "@knowledge/modules/common/AttachmentIcon";
import PanelEmptyColumn from "@knowledge/modules/search/components/PanelEmptyColumn";
import { PageTitle } from "@knowledge/modules/common/PageTitle";
import { dummyDraftListData } from "@knowledge/modules/drafts/state/dummyDrafts";
import DraftList from "@knowledge/modules/drafts/components/DraftList";
import { IDraftPreview } from "./DraftPreview";

interface IProps {
    device: Devices;
    data: IDraftPreview[];
    hasMoreResults: boolean;
    loadMoreResults?: () => void;
}

class DraftsLayout extends React.Component<IProps> {
    public render() {
        const { device } = this.props;
        const isFullWidth = [Devices.DESKTOP, Devices.NO_BLEED].includes(device); // This compoment doesn't care about the no bleed, it's the same as desktop

        return (
            <Container>
                <PanelLayout device={this.props.device}>
                    {isFullWidth && <PanelLayout.LeftTop>{<PanelEmptyColumn />}</PanelLayout.LeftTop>}
                    <PanelLayout.MiddleTop>
                        <PanelWidget>
                            <PageTitle title={t("Drafts")} device={device} />
                        </PanelWidget>
                    </PanelLayout.MiddleTop>
                    <PanelLayout.MiddleBottom>
                        <PanelWidgetVerticalPadding>
                            {<DraftList data={dummyDraftListData} hasMoreResults={this.props.hasMoreResults} />}
                        </PanelWidgetVerticalPadding>
                    </PanelLayout.MiddleBottom>
                    {isFullWidth && <PanelLayout.RightTop>{<PanelEmptyColumn />}</PanelLayout.RightTop>}
                </PanelLayout>
            </Container>
        );
    }
}

export default withDevice<IProps>(DraftsLayout);
