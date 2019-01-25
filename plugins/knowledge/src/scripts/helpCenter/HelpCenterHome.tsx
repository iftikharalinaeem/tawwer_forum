/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IKnowledgeBase } from "@knowledge/knowledge-bases/KnowledgeBaseModel";
import NavigationActions from "@knowledge/modules/navigation/NavigationActions";
import NavigationSelector, { IHelpData } from "@knowledge/modules/navigation/NavigationSelector";
import ErrorPage from "@knowledge/routes/ErrorPage";
import { IStoreState } from "@knowledge/state/model";
import { ILoadable, LoadStatus } from "@library/@types/api";
import apiv2 from "@library/apiv2";
import { Devices } from "@library/components/DeviceChecker";
import DocumentTitle from "@library/components/DocumentTitle";
import FullPageLoader from "@library/components/FullPageLoader";
import VanillaHeader from "@library/components/headers/VanillaHeader";
import Container from "@library/components/layouts/components/Container";
import PanelLayout from "@library/components/layouts/PanelLayout";
import React from "react";
import { connect } from "react-redux";
import HelpCenterNavigation from "@knowledge/helpCenter/components/HelpCenterNavigation";

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
            return <ErrorPage loadable={this.props} />;
        }

        return (
            <Container>
                <DocumentTitle title={knowledgeBase.name}>
                    <VanillaHeader />
                </DocumentTitle>
                <PanelLayout device={Devices.DESKTOP}>
                    <PanelLayout.MiddleBottom>
                        <h1>{knowledgeBase.name}</h1>
                        <HelpCenterNavigation data={data!} />
                    </PanelLayout.MiddleBottom>
                </PanelLayout>
            </Container>
        );
    }

    /**
     * Request our data if it hasn't been provided yet.
     */
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

function mapStateToProps(state: IStoreState, ownProps: IOwnProps) {
    const { knowledgeBaseID } = ownProps.knowledgeBase;
    const knowledgeState = state.knowledge.navigation;
    const loadStatus = knowledgeState.fetchLoadablesByKbID[knowledgeBaseID] || { status: LoadStatus.PENDING };

    let data: IHelpData | undefined;
    if (loadStatus.status === LoadStatus.SUCCESS) {
        data = NavigationSelector.selectHelpCenterNome(knowledgeState.navigationItems, ownProps.knowledgeBase);
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
