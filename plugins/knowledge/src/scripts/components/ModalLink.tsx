/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { Link, LinkProps, withRouter, RouteComponentProps } from "react-router-dom";
import { LocationDescriptor } from "history";

interface IProps extends LinkProps, RouteComponentProps<{}> {
    to: string;
}

export class ModalLink extends React.Component<IProps> {
    public render() {
        const to: LocationDescriptor = {
            pathname: this.props.to,
            state: {
                modal: true,
                lastLocation: this.props.location,
            },
        };
        return <Link to={to} children={this.props.children} />;
    }
}

export default withRouter(ModalLink);
