/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IKnowledgeAppStoreState } from "@knowledge/state/model";
import MessageAndRefresh from "@library/messages/MessageAndRefresh";
import React from "react";
import { connect } from "react-redux";

type IProps = ReturnType<typeof mapStateToProps>;

const defaultProps: IProps = {
    localDeploymentKey: null,
    serverDeploymentKey: null,
};

const UpdatePrompt: React.FunctionComponent<IProps> = (props: IProps = defaultProps) => {
    const { localDeploymentKey, serverDeploymentKey } = props;
    if (localDeploymentKey !== null && serverDeploymentKey !== null && localDeploymentKey !== serverDeploymentKey) {
        return <MessageAndRefresh isFixed />;
    }

    return null;
};

function mapStateToProps(state: IKnowledgeAppStoreState) {
    return {
        localDeploymentKey: state.server.localDeploymentKey,
        serverDeploymentKey: state.server.serverDeploymentKey,
    };
}

export default connect(mapStateToProps)(UpdatePrompt);
