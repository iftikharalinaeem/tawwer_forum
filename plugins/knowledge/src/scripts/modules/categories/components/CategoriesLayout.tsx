/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import * as React from "react";
import Container from "@knowledge/layouts/components/Container";
import PanelLayout, { PanelWidget } from "@knowledge/layouts/PanelLayout";
import { Devices } from "@library/components/DeviceChecker";
import { withDevice } from "@knowledge/contexts/DeviceContext";
import { IPageHeading } from "@knowledge/modules/article/components/ArticleTOC";
import { InlineTypes } from "@library/components/Sentence";
import { IKbCategoriesState } from "@knowledge/modules/categories/CategoryModel";
import Breadcrumbs, { ICrumb } from "@library/components/Breadcrumbs";
import { t } from "@library/application";
import PageTitle from "@knowledge/modules/common/PageTitle";
import CategoryMenu from "@knowledge/modules/categories/components/CategoryMenu";
import { IKbCategoryFragment } from "@knowledge/@types/api/kbCategory";
import SearchResults from "@knowledge/modules/common/SearchResults";
import { IResult } from "@knowledge/modules/common/SearchResult";
import { AttachmentType } from "@knowledge/modules/common/AttachmentIcons";
import { dummyMetaData } from "../state/dummyMetaData";
import { LoadStatus } from "@library/@types/api";

interface IProps {
    children: IKbCategoryFragment[];
    device: Devices;
}

export class CategoriesLayout extends React.Component<IProps> {
    public render() {
        return (
            <Container>
                <PanelLayout device={this.props.device}>
                    <PanelLayout.Breadcrumbs>
                        <PanelWidget>
                            <Breadcrumbs>{this.dummyBreadcrumbData}</Breadcrumbs>
                        </PanelWidget>
                    </PanelLayout.Breadcrumbs>
                    <PanelLayout.MiddleTop>
                        <PageTitle
                            backUrl="#Back"
                            title={t("[Category Name]")}
                            menu={<CategoryMenu />}
                            meta={dummyMetaData as any}
                        />
                    </PanelLayout.MiddleTop>
                    <PanelLayout.MiddleBottom>
                        <SearchResults children={this.dummySearchResults as any} />
                    </PanelLayout.MiddleBottom>
                </PanelLayout>
            </Container>
        );
    }

    private get dummyBreadcrumbData(): ICrumb[] {
        return [
            {
                name: "Home",
                url: "/kb",
            },
            {
                name: "two",
                url: "#",
            },
            {
                name: "three",
                url: "#",
            },
            {
                name: "four",
                url: "#",
            },
            {
                name: "five",
                url: "#",
            },
            {
                name: "six",
                url: "#",
            },
        ];
    }

    private get dummySearchResults(): any {
        return [
            {
                name: "Getting Help with your community",
                meta: dummyMetaData,
                url: "#",
                excerpt:
                    "Standard with your order of the Plantronics CT12 wireless headset phone is a two in one headset that is convertible so you can use it over the head for stability or over the ear for convenience. It has a microphone that is especially designed to cancel out background noises as well as top notch clarity of sound.",
                image: "https://us.v-cdn.net/5022541/uploads/942/WKEOVS2LF32Y.png",
                attachments: [
                    {
                        name: "Some Word Document",
                        type: AttachmentType.WORD,
                    },
                    {
                        name: "Some Word Document 2",
                        type: AttachmentType.FILE,
                    },
                    {
                        name: "Some Word Document 3",
                        type: AttachmentType.PDF,
                    },
                    {
                        name: "Some Word Document 4",
                        type: AttachmentType.EXCEL,
                    },
                    {
                        name: "Some Word Document 5",
                        type: AttachmentType.WORD,
                    },
                    {
                        name: "Some Word Document 6",
                        type: AttachmentType.EXCEL,
                    },
                    {
                        name: "Some Word Document 7",
                        type: AttachmentType.WORD,
                    },
                    {
                        name: "Some Word Document 8",
                        type: AttachmentType.WORD,
                    },
                    {
                        name: "Some Word Document 9",
                        type: AttachmentType.WORD,
                    },
                ],
            },
            {
                name: "Getting Help with your community",
                meta: dummyMetaData,
                url: "#",
                excerpt: "Standard with your order.",
                image: "https://library.vanillaforums.com/wp-content/uploads/2018/09/Case-study-headers-2018-1.png",
            },
            {
                name: "Getting Help with your community",
                meta: dummyMetaData,
                url: "#",
                excerpt: "Standard with your order.",
                attachments: [
                    {
                        name: "Some Word Document 1",
                        type: AttachmentType.EXCEL,
                    },
                ],
                image: "https://library.vanillaforums.com/wp-content/uploads/2018/09/Case-study-headers-2018-1.png",
            },
            {
                name:
                    "Getting Help with your communityGetting Help with your communityGetting Help with your communityGetting Help with your communityGetting Help with your communityGetting Help with your communityGetting Help with your communityGetting Help with your community",
                meta: dummyMetaData,
                url: "#",
                excerpt:
                    "Standard with your order.Standard with your order.Standard with your order.Standard with your order.Standard with your order.Standard with your order.Standard with your order.Standard with your order.Standard with your order.Standard with your order.Standard with your order.Standard with your order.Standard with your order.Standard with your order.Standard with your order.Standard with your order.Standard with your order.Standard with your order.Standard with your order.Standard with your order.Standard with your order.Standard with your order.",
                attachments: [
                    {
                        name: "Some Word Document 1",
                        type: AttachmentType.WORD,
                    },
                    {
                        name: "Some Word Document 2",
                        type: AttachmentType.PDF,
                    },
                    {
                        name: "Some Word Document 3",
                        type: AttachmentType.FILE,
                    },
                    {
                        name: "Some Word Document 4",
                        type: AttachmentType.EXCEL,
                    },
                ],
            },
        ];
    }
}

export default withDevice<IProps>(CategoriesLayout);
