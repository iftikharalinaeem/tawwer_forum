/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { connect } from "react-redux";
import { IStoreState } from "@knowledge/state/model";
import FormError from "@library/components/forms/FormError";
import { NavigationActionType } from "@knowledge/navigation/state/NavigationModel";
import { getGlobalErrorMessage } from "@library/apiv2";

class NavigationManagerErrors extends React.Component<IProps> {
    public render(): React.ReactNode {
        const { currentError } = this.props;

        if (!currentError) {
            return null;
        }

        const retryHandler = currentError.type === NavigationActionType.MOVE ? this.handleRetryMove : undefined;

        return (
            <FormError onDismissClick={this.props.onClear} onRetryClick={retryHandler}>
                {getGlobalErrorMessage(currentError.error)}
            </FormError>
        );
    }

    private handleErrorDismiss = () => {
        this.props.onClear();
    };

    private handleRetryMove = () => {};
}

interface IOwnProps {}

type IProps = IOwnProps & ReturnType<typeof mapStateToProps> & ReturnType<typeof mapDispatchToProps>;

function mapStateToProps(state: IStoreState, ownProps: IOwnProps) {
    return {
        currentError: state.knowledge.navigation.currentError,
    };
}

function mapDispatchToProps(dispatch: any) {
    return {
        onClear: () => {},
    };
}

export default connect(
    mapStateToProps,
    mapDispatchToProps,
)(NavigationManagerErrors);
