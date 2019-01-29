/**
 * @author Stéphane (slafleche) LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */
import { IArticle } from "@knowledge/@types/api";
import ArticleMenu from "@knowledge/modules/article/ArticleMenu";
import { ArticleMeta } from "@knowledge/modules/article/components/ArticleMeta";
import ArticleTOC from "@knowledge/modules/article/components/ArticleTOC";
import PageTitle from "@knowledge/modules/common/PageTitle";
import Navigation from "@knowledge/modules/navigation/Navigation";
import NavigationBreadcrumbs from "@knowledge/modules/navigation/NavigationBreadcrumbs";
import { NavigationRecordType } from "@knowledge/modules/navigation/NavigationModel";
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
    currentNavigationCategory?: INavigationItem;
}

/**
 * Implements the article's layout
 */
export class ArticleLayout extends React.Component<IProps> {
    public render() {
        const { article, currentNavigationCategory, messages } = this.props;
        const { articleID } = article;

        const activeRecord = { recordID: articleID, recordType: NavigationRecordType.ARTICLE };

        let title = "";
        if (currentNavigationCategory) {
            title = currentNavigationCategory.name;
        }

        return (
            <Container>
                <VanillaHeader
                    isFixed={true}
                    title={title}
                    mobileDropDownContent={
                        <Navigation collapsible={true} activeRecord={activeRecord} kbID={this.props.kbID} />
                    }
                />
                <PanelLayout
                    breadcrumbs={
                        this.props.device !== Devices.MOBILE && (
                            <PanelWidget>
                                <NavigationBreadcrumbs activeRecord={activeRecord} />
                            </PanelWidget>
                        )
                    }
                    leftBottom={
                        <PanelWidget>
                            <Navigation collapsible={true} activeRecord={activeRecord} kbID={1} />
                        </PanelWidget>
                    }
                    middleTop={
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
                    }
                    middleBottom={
                        <PanelWidget>
                            <UserContent content={article.body} />
                        </PanelWidget>
                    }
                    rightTop={
                        article.outline &&
                        article.outline.length > 0 && (
                            <PanelWidget>
                                <ArticleTOC items={article.outline} />
                            </PanelWidget>
                        )
                    }
                />
            </Container>
        );
    }
}

/**
 * Injectable props from the application state.
 */
interface IInjectableStoreState {
    currentNavigationCategory?: INavigationItem;
}

/**
 * Map elements in the application state to this component's properties.
 * @param state - The full, current state of the application.
 * @param ownProps - Current component instance props.
 */
function mapStateToProps(state: IStoreState, ownProps: IProps): IInjectableStoreState {
    const { article } = ownProps;
    const { knowledgeCategoryID } = article;

    return {
        currentNavigationCategory: knowledgeCategoryID
            ? NavigationSelector.selectCategory(knowledgeCategoryID, state.knowledge.navigation.navigationItems)
            : undefined,
    };
}

export default withDevice(connect(mapStateToProps)(ArticleLayout));
