/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { connect } from "react-redux";
import { IStoreState } from "@knowledge/state/model";
import { IKnowledgeBase } from "@knowledge/knowledge-bases/KnowledgeBaseModel";
import NavigationSelector from "@knowledge/modules/navigation/NavigationSelector";
import { NavigationRecordType, IKbNavigationItem } from "@knowledge/modules/navigation/NavigationModel";
import apiv2 from "@library/apiv2";
import NavigationActions from "@knowledge/modules/navigation/NavigationActions";
import DocumentTitle from "@library/components/DocumentTitle";
import Container from "@library/components/layouts/components/Container";
import VanillaHeader from "@library/components/headers/VanillaHeader";
import PanelLayout from "@library/components/layouts/PanelLayout";
import { Devices } from "@library/components/DeviceChecker";
import { LoadStatus, INavigationTreeItem, ILoadable } from "@library/@types/api";
import FullPageLoader from "@library/components/FullPageLoader";
import ErrorPage from "@knowledge/routes/ErrorPage";

export class HelpCenterHome extends React.Component<IProps> {
    public render() {
        const { knowledgeBase, status, data } = this.props;

        if ([LoadStatus.PENDING, LoadStatus.LOADING].includes(status)) {
            return <FullPageLoader />;
        }

        if (status === LoadStatus.ERROR) {
            return <ErrorPage loadable={this.props} />;
        }

        return (
            <Container>
                <DocumentTitle title={knowledgeBase.name}>
                    <VanillaHeader />
                </DocumentTitle>
                <PanelLayout device={Devices.DESKTOP}>
                    <PanelLayout.MiddleBottom>
                        <h2>Hello {knowledgeBase.name}</h2>
                    </PanelLayout.MiddleBottom>
                </PanelLayout>
            </Container>
        );
    }

    public componentDidMount() {
        const { status, requestData } = this.props;
        if (status === LoadStatus.PENDING) {
            requestData();
        }
    }
}

interface IOwnProps {
    knowledgeBase: IKnowledgeBase;
}

type IProps = IOwnProps & ReturnType<typeof mapStateToProps> & ReturnType<typeof mapDispatchToProps>;

type NavArticle = IKbNavigationItem<NavigationRecordType.ARTICLE>;
type NavCategory = IKbNavigationItem<NavigationRecordType.KNOWLEDGE_CATEGORY>;

interface IHelpGroup {
    category: NavCategory;
    articles: NavArticle[];
}

interface IHelpData {
    groups: IHelpGroup[];
    ungroupedArticles: NavArticle[];
}

function mapStateToProps(state: IStoreState, ownProps: IOwnProps) {
    const { knowledgeBaseID } = ownProps.knowledgeBase;
    const rootNavItemID = NavigationRecordType.KNOWLEDGE_CATEGORY + ownProps.knowledgeBase.rootCategoryID;
    const knowledgeState = state.knowledge.navigation;
    const loadStatus = knowledgeState.fetchLoadablesByKbID[knowledgeBaseID] || { status: LoadStatus.PENDING };

    let data: IHelpData | undefined;
    if (loadStatus.status === LoadStatus.SUCCESS) {
        const treeData = NavigationSelector.selectNavTree(knowledgeState.navigationItems, rootNavItemID);
        data = {
            groups: [],
            ungroupedArticles: [],
        };

        // Help center data only iterates through 2 levels of nav data.
        for (const record of treeData.children) {
            switch (record.recordType) {
                case NavigationRecordType.ARTICLE: {
                    const { children, ...article } = record;
                    data.ungroupedArticles.push(article as NavArticle);
                    break;
                }
                case NavigationRecordType.KNOWLEDGE_CATEGORY: {
                    const { children, ...category } = record;
                    const group: IHelpGroup = {
                        category: category as NavCategory,
                        articles: [],
                    };
                    for (const child of children) {
                        if (child.recordType === NavigationRecordType.ARTICLE) {
                            const { children: unused, ...article } = child;
                            group.articles.push(article as NavArticle);
                        }
                    }
                    data.groups.push(group);
                    break;
                }
            }
        }
    }

    const loadable: ILoadable<IHelpData> = {
        ...loadStatus,
        data,
    };

    return loadable;
}

function mapDispatchToProps(dispatch: any, ownProps: IOwnProps) {
    const navActions = new NavigationActions(dispatch, apiv2);
    return {
        requestData: () => navActions.getNavigationFlat(ownProps.knowledgeBase.knowledgeBaseID),
    };
}

export default connect(
    mapStateToProps,
    mapDispatchToProps,
)(HelpCenterHome);
