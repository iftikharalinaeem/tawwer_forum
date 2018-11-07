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

interface IProps extends IDeviceProps {}

interface IState {
    data: IDraftPreview[];
    truncatedResults: boolean;
}

/**
 * Page component for drafts page
 */
export class SearchPage extends React.Component<IProps, IState> {
    public constructor(props) {
        super(props);
        this.state = {
            data: dummyDraftListData,
            truncatedResults: this.truncatedResults(),
        };
    }

    public render() {
        return (
            <PageLoader status={LoadStatus.SUCCESS}>
                <DocumentTitle title={t("Drafts")}>
                    <DraftsLayout
                        {...this.props}
                        data={this.state.data || []}
                        loadMoreResults={this.loadMoreResults}
                        truncatedResults={this.truncatedResults()}
                    />
                </DocumentTitle>
            </PageLoader>
        );
    }

    // Temporary function to simulate loading data from API
    private loadMoreResults() {
        const newData = [...this.state.data, ...dummyDraftListData];
        this.setState({
            data: newData,
            truncatedResults: this.truncatedResults(newData),
        });
    }

    // Temporary function to simulate checking if we have more data
    private truncatedResults(data: any[]) {
        return data.length >= 15;
    }
}

export default withDevice(SearchPage);
