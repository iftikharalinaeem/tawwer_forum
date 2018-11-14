/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { t } from "@library/application";
import DraftsLayout from "@knowledge/modules/drafts/components/DraftsLayout";
import { IDeviceProps } from "@library/components/DeviceChecker";
import PageLoader from "@library/components/PageLoader";
import { withDevice } from "@knowledge/contexts/DeviceContext";
import DocumentTitle from "@library/components/DocumentTitle";
import { LoadStatus } from "@library/@types/api/core";
import { IDraftPreview } from "@knowledge/modules/drafts/components/DraftPreview";
import { dummyDraftListData } from "./state/dummyDrafts";
import ModalSizes from "@library/components/modal/ModalSizes";
import Modal from "@library/components/modal/Modal";
import { uniqueIDFromPrefix } from "@library/componentIDs";
import { RouteComponentProps } from "react-router-dom";

interface IOwnProps
    extends RouteComponentProps<{
            id?: string;
        }> {}

interface IProps extends IOwnProps, IDeviceProps {}

interface IState {
    data: IDraftPreview[];
    hasMoreResults: boolean;
}

/**
 * Page component for drafts page
 */
export class DraftsPage extends React.Component<IProps, IState> {
    private id = uniqueIDFromPrefix("draftsPage");
    public constructor(props) {
        super(props);
        this.state = {
            data: dummyDraftListData,
            hasMoreResults: true,
        };
    }

    public render() {
        return (
            <Modal
                titleID={this.titleID}
                size={ModalSizes.FULL_SCREEN}
                exitHandler={this.navigateToBacklink}
                elementToFocusOnExit={document.activeElement as HTMLElement}
            >
                <PageLoader status={LoadStatus.SUCCESS}>
                    <DocumentTitle title={t("Drafts")}>
                        <DraftsLayout
                            {...this.props}
                            data={this.state.data}
                            loadMoreResults={this.loadMoreResults}
                            hasMoreResults={this.state.hasMoreResults}
                        />
                    </DocumentTitle>
                </PageLoader>
            </Modal>
        );
    }

    // Temporary function to simulate loading data from API
    private loadMoreResults = () => {
        const newData = [...this.state.data, ...dummyDraftListData];
        this.setState({
            data: newData,
            hasMoreResults: this.hasMoreResults(newData),
        });
        this.forceUpdate();
    };

    // Temporary function to simulate checking if we have more data
    private hasMoreResults = (data: any[]) => {
        return data.length <= 15;
    };

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

export default withDevice(DraftsPage);
