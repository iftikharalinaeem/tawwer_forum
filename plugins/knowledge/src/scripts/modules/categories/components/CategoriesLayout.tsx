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

interface IProps {
    children: IKbCategoryFragment[];
    device: Devices;
}

interface IState {}

export class CategoriesLayout extends React.Component<IProps, IState> {
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
                        <SearchResults />
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
