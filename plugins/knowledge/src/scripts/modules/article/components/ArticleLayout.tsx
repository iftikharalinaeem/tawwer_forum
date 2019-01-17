/**
 * @author Stéphane (slafleche) LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */
import { IArticle, NavigationRecordType } from "@knowledge/@types/api";
import ArticleMenu from "@knowledge/modules/article/ArticleMenu";
import { ArticleMeta } from "@knowledge/modules/article/components/ArticleMeta";
import ArticleTOC from "@knowledge/modules/article/components/ArticleTOC";
import PageTitle from "@knowledge/modules/common/PageTitle";
import Navigation from "@knowledge/modules/navigation/Navigation";
import NavigationBreadcrumbs from "@knowledge/modules/navigation/NavigationBreadcrumbs";
import { INormalizedNavigationItems } from "@knowledge/modules/navigation/NavigationModel";
import NavigationSelector from "@knowledge/modules/navigation/NavigationSelector";
import { IStoreState } from "@knowledge/state/model";
import { INavigationItem } from "@library/@types/api";
import { ICrumb } from "@library/components/Breadcrumbs";
import { Devices } from "@library/components/DeviceChecker";
import VanillaHeader from "@library/components/headers/VanillaHeader";
import Container from "@library/components/layouts/components/Container";
import PanelLayout, { PanelWidget } from "@library/components/layouts/PanelLayout";
import UserContent from "@library/components/UserContent";
import { withDevice } from "@library/contexts/DeviceContext";
import * as React from "react";
import { connect } from "react-redux";

interface IProps {
    article: IArticle;
    device: Devices;
    breadcrumbData: ICrumb[];
    messages?: React.ReactNode;
    kbID: number;
    navigationItems: INormalizedNavigationItems;
}

/**
 * Implements the article's layout
 */
export class ArticleLayout extends React.Component<IProps> {
    public render() {
        const { article, navigationItems, messages } = this.props;
        const { articleID, knowledgeCategoryID } = article;

        const activeRecord = { recordID: articleID, recordType: NavigationRecordType.ARTICLE };

        let title = "";
        if (knowledgeCategoryID) {
            const currentCategory = NavigationSelector.selectCategory(knowledgeCategoryID, navigationItems);
            title = currentCategory ? currentCategory.name : "";
        }

        return (
            <React.Fragment>
                <Container>
                    <VanillaHeader
                        title={title}
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

interface IInjectableStoreState {
    navigationItems: INormalizedNavigationItems;
}

function mapStateToProps(state: IStoreState): IInjectableStoreState {
    return {
        navigationItems: state.knowledge.navigation.navigationItems,
    };
}

export default connect(mapStateToProps)(withDevice<IProps>(ArticleLayout));
