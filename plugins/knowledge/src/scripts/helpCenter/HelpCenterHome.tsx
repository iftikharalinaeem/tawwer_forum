/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IKnowledgeBase } from "@knowledge/knowledge-bases/KnowledgeBaseModel";
import NavigationActions from "@knowledge/navigation/state/NavigationActions";
import NavigationSelector from "@knowledge/navigation/state/NavigationSelector";
import ErrorPage, { DefaultError } from "@knowledge/routes/ErrorPage";
import { IStoreState } from "@knowledge/state/model";
import { ILoadable, LoadStatus, ILinkListData } from "@library/@types/api";
import apiv2 from "@library/apiv2";
import { Devices } from "@library/components/DeviceChecker";
import DocumentTitle from "@library/components/DocumentTitle";
import FullPageLoader from "@library/components/FullPageLoader";
import VanillaHeader from "@library/components/headers/VanillaHeader";
import Container from "@library/components/layouts/components/Container";
import React from "react";
import { connect } from "react-redux";
import HelpCenterNavigation from "@knowledge/helpCenter/components/HelpCenterNavigation";
import ScreenReaderContent from "@library/components/ScreenReaderContent";
import WidgetContainer from "@library/components/layouts/components/WidgetContainer";

/**
 * Component representing the the full home page of a help center.
 */
export class HelpCenterHome extends React.Component<IProps> {
    public render() {
        const { knowledgeBase, status, data } = this.props;

        if ([LoadStatus.PENDING, LoadStatus.LOADING].includes(status)) {
            return <FullPageLoader />;
        }

        if (status === LoadStatus.ERROR) {
            return <ErrorPage error={this.props.error} />;
        }

        if (knowledgeBase.countArticles === 0) {
            return (
                <ErrorPage
                    defaultError={DefaultError.NO_ARTICLES}
                    knowledgeBaseID={knowledgeBase.knowledgeBaseID}
                    knowledgeCategoryID={knowledgeBase.rootCategoryID}
                />
            );
        }

        return (
            <Container>
                <DocumentTitle title={knowledgeBase.name}>
                    <VanillaHeader />
                </DocumentTitle>

                {/*For Screen Readers / SEO*/}
                <ScreenReaderContent>
                    <h1>{knowledgeBase.name}</h1>
                </ScreenReaderContent>
                <WidgetContainer>
                    <HelpCenterNavigation data={data!} />
                </WidgetContainer>
            </Container>
        );
    }

    /**
     * Request our data if it hasn't been provided yet.
     */
    public componentDidMount() {
        const { status, requestData } = this.props;
        if (status === LoadStatus.PENDING) {
            void requestData();
        }
    }
}

interface IOwnProps {
    knowledgeBase: IKnowledgeBase;
}

type IProps = IOwnProps & ReturnType<typeof mapStateToProps> & ReturnType<typeof mapDispatchToProps>;

function mapStateToProps(state: IStoreState, ownProps: IOwnProps) {
    const { knowledgeBaseID } = ownProps.knowledgeBase;
    const knowledgeState = state.knowledge.navigation;
    const loadStatus = knowledgeState.fetchLoadablesByKbID[knowledgeBaseID] || { status: LoadStatus.PENDING };

    let data: ILinkListData | undefined;
    if (loadStatus.status === LoadStatus.SUCCESS) {
        data = NavigationSelector.selectHelpCenterNome(knowledgeState.navigationItems, ownProps.knowledgeBase);
    }

    const loadable: ILoadable<ILinkListData> = {
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
