/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { connect } from "react-redux";
import { IKnowledgeAppStoreState } from "@knowledge/state/model";
import { NavigationActionType } from "@knowledge/navigation/state/NavigationModel";
import apiv2, { getGlobalErrorMessage } from "@library/apiv2";
import NavigationActions from "@knowledge/navigation/state/NavigationActions";
import Message from "@library/messages/Message";
import ButtonLoader from "@library/loaders/ButtonLoader";
import { t } from "@library/utility/appUtils";
import { navigationManagerClasses } from "@knowledge/navigation/navigationManagerStyles";
import Container from "@library/layout/components/Container";
import { ErrorIcon } from "@library/icons/common";

type RetryHandler = (() => void) | null;

class NavigationManagerErrors extends React.Component<IProps> {
    public render(): React.ReactNode {
        const { currentError } = this.props;

        if (!currentError) {
            return null;
        }

        const classes = navigationManagerClasses();
        const retryHandler = this.getRetryHandler();

        return (
            <Container>
                <Message
                    isFixed
                    icon={<ErrorIcon />}
                    onConfirm={retryHandler ? this.handleRetry : undefined}
                    confirmText={currentError.isLoading ? <ButtonLoader /> : t("Retry")}
                    onCancel={() => this.props.onClear()}
                    stringContents={
                        getGlobalErrorMessage(currentError.error) ||
                        t("Something went wrong while contacting the server.")
                    }
                />
            </Container>
        );
    }

    private getRetryHandler = (): RetryHandler => {
        const { currentError } = this.props;
        if (!currentError) {
            return null;
        }
        switch (currentError.type) {
            case NavigationActionType.GET:
                return this.props.requestData;
            case NavigationActionType.MOVE:
                return this.props.syncData;
            default:
                return null;
        }
    };

    private handleRetry = () => {
        const handler = this.getRetryHandler();
        if (handler) {
            this.props.onRetry();
            handler();
        }
    };
}

interface IOwnProps {
    knowledgeBaseID: number;
}

type IProps = IOwnProps & ReturnType<typeof mapStateToProps> & ReturnType<typeof mapDispatchToProps>;

function mapStateToProps(state: IKnowledgeAppStoreState, ownProps: IOwnProps) {
    return {
        currentError: state.knowledge.navigation.currentError,
    };
}

function mapDispatchToProps(dispatch: any, ownProps: IOwnProps) {
    const navigationActions = new NavigationActions(dispatch, apiv2);

    return {
        onClear: navigationActions.clearErrors,
        onRetry: navigationActions.markRetryAsLoading,
        requestData: () => navigationActions.getNavigationFlat(ownProps.knowledgeBaseID, true),
        syncData: () => navigationActions.patchNavigationFlat(ownProps.knowledgeBaseID),
    };
}

export default connect(mapStateToProps, mapDispatchToProps)(NavigationManagerErrors);
