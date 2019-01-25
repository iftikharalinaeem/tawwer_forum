/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { connect } from "react-redux";
import { IStoreState } from "@knowledge/state/model";

export class HelpCenterHome extends React.Component<IProps> {
    public render() {
        // Render component here.
        return <div />;
    }

    public componentDidMount() {
        // Request data here. Be sure to check if it's already loaded or not.
    }
}

interface IOwnProps {}

type IProps = IOwnProps & ReturnType<typeof mapStateToProps> & ReturnType<typeof mapDispatchToProps>;

function mapStateToProps(state: IStoreState, ownProps: IOwnProps) {
    return {};
}

function mapDispatchToProps(dispatch: any) {
    return {};
}

export default connect(
    mapStateToProps,
    mapDispatchToProps,
)(HelpCenterHome);
