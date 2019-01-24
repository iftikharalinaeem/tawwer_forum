/**
 * @author Stéphane (slafleche) LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */
import * as React from "react";
import { Devices } from "@library/components/DeviceChecker";
import { IArticle } from "@knowledge/@types/api";
import PanelLayout, { PanelWidget } from "@library/components/layouts/PanelLayout";
import ArticleTOC from "@knowledge/modules/article/components/ArticleTOC";
import ArticleMenu from "@knowledge/modules/article/ArticleMenu";
import { withDevice } from "@library/contexts/DeviceContext";
import { ICrumb } from "@library/components/Breadcrumbs";
import PageTitle from "@knowledge/modules/common/PageTitle";
import UserContent from "@library/components/UserContent";
import { ArticleMeta } from "@knowledge/modules/article/components/ArticleMeta";
import VanillaHeader from "@library/components/headers/VanillaHeader";
import Container from "@library/components/layouts/components/Container";
import Navigation from "@knowledge/modules/navigation/Navigation";
import NavigationBreadcrumbs from "@knowledge/modules/navigation/NavigationBreadcrumbs";
import { NavigationRecordType } from "@knowledge/modules/navigation/NavigationModel";

interface IProps {
    article: IArticle;
    device: Devices;
    breadcrumbData: ICrumb[];
    messages?: React.ReactNode;
    kbID: number;
}

/**
 * Implements the article's layout
 */
export class ArticleLayout extends React.Component<IProps> {
    public render() {
        const { article, messages } = this.props;
        const { articleID } = article;

        const activeRecord = { recordID: articleID, recordType: NavigationRecordType.ARTICLE };

        return (
            <React.Fragment>
                <Container>
                    <VanillaHeader
                        title={article.name}
                        mobileDropDownContent={
                            <Navigation collapsible={true} activeRecord={activeRecord} kbID={this.props.kbID} />
                        }
                    />
                    <PanelLayout device={this.props.device}>
                        {this.props.device !== Devices.MOBILE && (
                            <PanelLayout.Breadcrumbs>
                                <PanelWidget>
                                    <NavigationBreadcrumbs activeRecord={activeRecord} />
                                </PanelWidget>
                            </PanelLayout.Breadcrumbs>
                        )}
                        <PanelLayout.LeftBottom>
                            <PanelWidget>
                                {<Navigation collapsible={true} activeRecord={activeRecord} kbID={1} />}
                            </PanelWidget>
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
                                    includeBackLink={this.props.device !== Devices.MOBILE}
                                />
                                {messages && <div className="messages">{messages}</div>}
                            </PanelWidget>
                        </PanelLayout.MiddleTop>
                        <PanelLayout.MiddleBottom>
                            <PanelWidget>
                                <UserContent content={article.body} />
                            </PanelWidget>
                        </PanelLayout.MiddleBottom>
                        {article.outline && article.outline.length > 0 && (
                            <PanelLayout.RightTop>
                                <PanelWidget>
                                    <ArticleTOC items={article.outline} />
                                </PanelWidget>
                            </PanelLayout.RightTop>
                        )}
                    </PanelLayout>
                </Container>
            </React.Fragment>
        );
    }
}

export default withDevice<IProps>(ArticleLayout);
