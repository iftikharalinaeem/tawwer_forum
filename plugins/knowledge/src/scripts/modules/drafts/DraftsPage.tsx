/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { t } from "@library/application";
import DraftsLayout from "@knowledge/modules/drafts/components/DraftsLayout";
import { IDeviceProps } from "@library/components/DeviceChecker";
import PageLoader from "@library/components/PageLoader";
import { withDevice } from "@library/contexts/DeviceContext";
import DocumentTitle from "@library/components/DocumentTitle";
import { LoadStatus } from "@library/@types/api/core";
import ModalSizes from "@library/components/modal/ModalSizes";
import Modal from "@library/components/modal/Modal";
import { uniqueIDFromPrefix } from "@library/componentIDs";
import { RouteComponentProps } from "react-router-dom";
import { connect } from "react-redux";
import DraftsPageActions from "@knowledge/modules/drafts/DraftsPageActions";
import DraftsPageModel, { IInjectableDraftsPageProps } from "@knowledge/modules/drafts/DraftsPageModel";
import apiv2 from "@library/apiv2";

interface IOwnProps
    extends RouteComponentProps<{
            id?: string;
        }> {}

interface IProps extends IOwnProps, IInjectableDraftsPageProps, IDeviceProps {
    actions: DraftsPageActions;
}

/**
 * Page component for drafts page
 */
export class DraftsPage extends React.Component<IProps> {
    private id = uniqueIDFromPrefix("draftsPage");

    public render() {
        return (
            <Modal
                titleID={this.titleID}
                size={ModalSizes.FULL_SCREEN}
                exitHandler={this.navigateToBacklink}
                elementToFocusOnExit={document.activeElement as HTMLElement}
            >
                <PageLoader status={this.props.userDrafts.status}>
                    <DocumentTitle title={t("Drafts")}>
                        <DraftsLayout {...this.props} data={this.props.userDrafts.data || []} />
                    </DocumentTitle>
                </PageLoader>
            </Modal>
        );
    }

    public componentDidMount() {
        void this.props.actions.getCurrentUserDrafts();
    }

    private get titleID() {
        return this.id + "-title";
    }

    /**
     * Route back to the previous location if its available.
     */
    private navigateToBacklink = () => {
        if (this.props.history.length > 1) {
            this.props.history.goBack();
        } else {
            this.props.history.push("/kb");
        }
    };
}

function mapDispatchToProps(dispatch) {
    return {
        actions: new DraftsPageActions(dispatch, apiv2),
    };
}

const withRedux = connect(
    DraftsPageModel.mapStateToProps,
    mapDispatchToProps,
);

export default withRedux(withDevice(DraftsPage));
