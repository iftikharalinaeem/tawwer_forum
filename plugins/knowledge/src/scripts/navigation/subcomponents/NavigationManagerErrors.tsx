/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { connect } from "react-redux";
import { IStoreState } from "@knowledge/state/model";
import FormError from "@library/components/forms/FormError";
import { NavigationActionType } from "@knowledge/navigation/state/NavigationModel";
import apiv2, { getGlobalErrorMessage } from "@library/apiv2";
import NavigationActions from "@knowledge/navigation/state/NavigationActions";

type RetryHandler = (() => void) | null;

class NavigationManagerErrors extends React.Component<IProps> {
    public render(): React.ReactNode {
        const { currentError } = this.props;

        if (!currentError) {
            return null;
        }

        const retryHandler = this.getRetryHandler();

        return (
            <FormError
                isRetryLoading={currentError.isLoading}
                onDismissClick={this.props.onClear}
                onRetryClick={retryHandler ? this.handleRetry : undefined}
            >
                {getGlobalErrorMessage(currentError.error)}
            </FormError>
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

function mapStateToProps(state: IStoreState, ownProps: IOwnProps) {
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

export default connect(
    mapStateToProps,
    mapDispatchToProps,
)(NavigationManagerErrors);
