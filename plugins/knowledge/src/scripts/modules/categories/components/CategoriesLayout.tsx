/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import Container from "@knowledge/layouts/components/Container";
import PanelLayout, { PanelWidget } from "@knowledge/layouts/PanelLayout";
import { Devices } from "@library/components/DeviceChecker";
import { withDevice } from "@knowledge/contexts/DeviceContext";
import { IPageHeading } from "@knowledge/modules/article/components/ArticleTOC";
import { InlineTypes } from "@library/components/Sentence";
import { IKbCategoriesState } from "@knowledge/modules/categories/state";
import Breadcrumbs, { ICrumb } from "@library/components/Breadcrumbs";
import { t } from "@library/application";
import PageTitle from "@knowledge/modules/common/PageTitle";
import CategoryMenu from "@knowledge/modules/categories/components/CategoryMenu";
import { IKbCategoryFragment } from "@knowledge/@types/api/kbCategory";
import SearchResults from "@knowledge/modules/common/SearchResults";
import { IResult } from "@knowledge/modules/common/SearchResult";
import { AttachmentDisplay, AttachmentType } from "@knowledge/modules/common/Attachments";

interface IProps {
    children: IKbCategoryFragment[];
    device: Devices;
}

export class CategoriesLayout extends React.Component<IProps> {
    public render() {
        const metaData = {
            children: [
                {
                    children: "By Todd Burry",
                    type: InlineTypes.TEXT,
                },
                {
                    children: [
                        {
                            children: "Last Updated:" + String.fromCharCode(160),
                            type: InlineTypes.TEXT,
                        },
                        {
                            timeStamp: "2018-03-03",
                            type: InlineTypes.DATETIME,
                            children: [
                                {
                                    children: "3 March 2018",
                                    type: InlineTypes.TEXT,
                                },
                            ],
                        },
                    ],
                },
                {
                    children: "ID #1029384756",
                    type: InlineTypes.TEXT,
                },
            ],
        };

        const searchResults = [
            {
                name: "Getting Help with your community",
                meta: metaData,
                url: "#",
                excerpt:
                    "Standard with your order of the Plantronics CT12 wireless headset phone is a two in one headset that is convertible so you can use it over the head for stability or over the ear for convenience. It has a microphone that is especially designed to cancel out background noises as well as top notch clarity of sound.",
                image: "https://us.v-cdn.net/5022541/uploads/942/WKEOVS2LF32Y.png",
                attachments: [
                    {
                        display: AttachmentDisplay.ICON,
                        children: [
                            {
                                name: "Some Word Document",
                                type: AttachmentType.WORD,
                            },
                        ],
                    },
                ],
            },
            {
                name: "Getting Help with your community",
                meta: metaData,
                url: "#",
                excerpt: "Standard with your order.",
                image: "https://library.vanillaforums.com/wp-content/uploads/2018/09/Case-study-headers-2018-1.png",
            },
            {
                name: "Getting Help with your community",
                meta: metaData,
                url: "#",
                excerpt: "Standard with your order.",
                attachments: [
                    {
                        display: AttachmentDisplay.ICON,
                        children: [
                            {
                                name: "Some Word Document 1",
                                type: AttachmentType.WORD,
                            },
                            {
                                name: "Some Word Document 2",
                                type: AttachmentType.WORD,
                            },
                            {
                                name: "Some Word Document 3",
                                type: AttachmentType.WORD,
                            },
                            {
                                name: "Some Word Document 4",
                                type: AttachmentType.WORD,
                            },
                            {
                                name: "Some Word Document 5",
                                type: AttachmentType.WORD,
                            },
                            {
                                name: "Some Word Document 6",
                                type: AttachmentType.WORD,
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
                ],
                image: "https://library.vanillaforums.com/wp-content/uploads/2018/09/Case-study-headers-2018-1.png",
            },
            {
                name:
                    "Getting Help with your communityGetting Help with your communityGetting Help with your communityGetting Help with your communityGetting Help with your communityGetting Help with your communityGetting Help with your communityGetting Help with your community",
                meta: metaData,
                url: "#",
                excerpt:
                    "Standard with your order.Standard with your order.Standard with your order.Standard with your order.Standard with your order.Standard with your order.Standard with your order.Standard with your order.Standard with your order.Standard with your order.Standard with your order.Standard with your order.Standard with your order.Standard with your order.Standard with your order.Standard with your order.Standard with your order.Standard with your order.Standard with your order.Standard with your order.Standard with your order.Standard with your order.",
                attachments: [
                    {
                        display: AttachmentDisplay.ICON,
                        children: [
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
                ],
            },
        ];

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
                            meta={metaData as any}
                        />
                    </PanelLayout.MiddleTop>
                    <PanelLayout.MiddleBottom>
                        <SearchResults children={searchResults as any} attachmentDisplay={AttachmentDisplay.ICON} />
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
}

export default withDevice<IProps>(CategoriesLayout);
