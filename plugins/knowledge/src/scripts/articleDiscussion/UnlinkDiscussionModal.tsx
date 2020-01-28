/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import ModalConfirm from "@library/modal/ModalConfirm";
import { t } from "@library/utility/appUtils";
import Translate from "@library/content/Translate";
import { LoadStatus } from "@library/@types/api/core";
import apiv2 from "@library/apiv2";

interface IProps {
    discussionID: number;
}

interface IState {
    unlinkStatus: LoadStatus;
}

export class UnlinkDiscussionModal extends React.Component<IProps, IState> {
    public state: IState = {
        unlinkStatus: LoadStatus.PENDING,
    };

    public render() {
        return (
            <ModalConfirm
                isVisible={true}
                title={t("Remove Article Link")}
                onConfirm={this.handleConfirm}
                isConfirmLoading={this.state.unlinkStatus === LoadStatus.LOADING}
                confirmTitle={t("Remove Link")}
            >
                <Translate source="This will remove the link to the article, but will not remove the article itself." />
            </ModalConfirm>
        );
    }

    private handleConfirm = async () => {
        this.setState({ unlinkStatus: LoadStatus.LOADING });
        await apiv2.delete(`/discussions/${this.props.discussionID}/canonical-url`);
        this.setState({ unlinkStatus: LoadStatus.SUCCESS });
        // Refresh the page.
        window.location.href = window.location.href;
    };
}
