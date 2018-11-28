/**
 * @author Stéphane (slafleche) LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */
import * as React from "react";
import { Devices } from "@library/components/DeviceChecker";
import { IArticle, ArticleStatus } from "@knowledge/@types/api";
import PanelLayout, { PanelWidget } from "@library/components/layouts/PanelLayout";
import ArticleTOC from "@knowledge/modules/article/components/ArticleTOC";
import RelatedArticles, { IInternalLink } from "@knowledge/modules/article/components/RelatedArticles";
import ArticleMenu from "@knowledge/modules/article/ArticleMenu";
import { withDevice } from "@library/contexts/DeviceContext";
import Breadcrumbs, { ICrumb } from "@library/components/Breadcrumbs";
import PageTitle from "@knowledge/modules/common/PageTitle";
import UserContent from "@library/components/UserContent";
import OtherLanguages from "@knowledge/modules/article/components/OtherLanguages";
import { ArticleMeta } from "@knowledge/modules/article/components/ArticleMeta";
import AttachmentList from "@knowledge/modules/article/components/AttachmentList";
import { AttachmentType } from "@library/components/attachments";
import { IFileAttachment } from "./AttachmentItem";
import VanillaHeader from "@library/components/headers/VanillaHeader";
import Container from "@library/components/layouts/components/Container";
import { dummyOtherLanguagesData } from "@library/state/dummyOtherLanguages";
import { dummyNavData } from "@knowledge/modules/categories/state/dummyNavData";
import SiteNav from "@library/components/siteNav/SiteNav";
import { isMobileDevice } from "react-select/lib/utils";

interface IProps {
    article: IArticle;
    device: Devices;
    breadcrumbData: ICrumb[];
    messages?: React.ReactNode;
    title?: string;
}

/**
 * Implements the article's layout
 */
export class ArticleLayout extends React.Component<IProps> {
    public render() {
        const { article, messages } = this.props;

        const mobileNav = <SiteNav expand={true}>{dummyNavData}</SiteNav>;
        const nav = <SiteNav expand={true}>{dummyNavData}</SiteNav>;

        return (
            <React.Fragment>
                <Container>
                    <VanillaHeader title={article.name} mobileDropDownContent={mobileNav} />
                    <PanelLayout device={this.props.device}>
                        {this.props.breadcrumbData.length > 1 &&
                            this.props.device !== Devices.MOBILE && (
                                <PanelLayout.Breadcrumbs>
                                    <PanelWidget>
                                        <Breadcrumbs>{this.props.breadcrumbData}</Breadcrumbs>
                                    </PanelWidget>
                                </PanelLayout.Breadcrumbs>
                            )}
                        <PanelLayout.LeftBottom>
                            <PanelWidget>{nav}</PanelWidget>
                        </PanelLayout.LeftBottom>
                        <PanelLayout.MiddleTop>
                            <PanelWidget>
                                <PageTitle
                                    title={article.name}
                                    actions={
                                        <ArticleMenu
                                            article={article}
                                            buttonClassName="pageTitle-menu"
                                            device={this.props.device}
                                        />
                                    }
                                    meta={
                                        <ArticleMeta
                                            updateUser={article.updateUser!}
                                            dateUpdated={article.dateUpdated}
                                            permaLink={article.url}
                                        />
                                    }
                                />
                                {messages && <div className="messages">{messages}</div>}
                            </PanelWidget>
                        </PanelLayout.MiddleTop>
                        <PanelLayout.MiddleBottom>
                            <PanelWidget>
                                <UserContent content={article.body} />
                                <AttachmentList attachments={this.articleAttachmentList} />
                            </PanelWidget>
                        </PanelLayout.MiddleBottom>
                        {article.outline &&
                            article.outline.length > 0 && (
                                <PanelLayout.RightTop>
                                    <PanelWidget>
                                        <ArticleTOC items={article.outline} />
                                    </PanelWidget>
                                </PanelLayout.RightTop>
                            )}
                        <PanelLayout.RightBottom>
                            <OtherLanguages {...dummyOtherLanguagesData} />
                            <RelatedArticles children={this.articleRelatedArticles} />
                        </PanelLayout.RightBottom>
                    </PanelLayout>
                </Container>
            </React.Fragment>
        );
    }

    private articleAttachmentList: IFileAttachment[] = [
        {
            url: "#",
            name: "Configuration_Guide_New.doc",
            title: "Guide",
            type: AttachmentType.WORD,
            dateUploaded: "2018-10-22T16:56:37.423Z",
            sizeValue: "1.1",
            sizeUnit: "MB",
            mimeType: "application/msword",
        },
        {
            url: "#",
            name: "Expenses.xls",
            type: AttachmentType.EXCEL,
            dateUploaded: "2018-10-22T16:56:37.423Z",
            sizeValue: "3.1",
            sizeUnit: "MB",
            mimeType: "application/vnd.ms-excel",
        },
        {
            url: "#",
            name: "PeeMartBrochure.pdf",
            type: AttachmentType.PDF,
            dateUploaded: "2018-10-22T16:56:37.423Z",
            sizeValue: "10.1",
            sizeUnit: "GB",
            mimeType: "application/pdf",
        },
        {
            url: "#",
            name: "HowToDrinkWater.txt",
            type: AttachmentType.FILE,
            dateUploaded: "2018-10-22T16:56:37.423Z",
            sizeValue: "1",
            sizeUnit: "KB",
            mimeType: "text/*",
        },
    ];

    private articleRelatedArticles: IInternalLink[] = [
        {
            name: "Overview",
            to: "#overview",
        },
        {
            name: "Changing Themes",
            to: "#changing-themes",
        },
        {
            name: "Configuration Guide",
            to: "#configuration-guide",
        },
        {
            name: "Theming Guide for Designers",
            to: "#theming-guide-for-designers",
        },
    ];
}

export default withDevice<IProps>(ArticleLayout);
