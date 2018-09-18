/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { Switch, RouteComponentProps } from "react-router-dom";
import { getPageRoutes } from "@knowledge/routes/pageRoutes";
import { getModalRoutes } from "@knowledge/routes/modalRoutes";

type IProps = RouteComponentProps<{}>;

/**
 * Routing component for pages in modals in the /kb directory.
 */
export default class KnowledgeRoutes extends React.Component<IProps> {
    public render() {
        const { location } = this.props;

        return (
            <React.Fragment>
                <Switch location={this.isModal ? this.lastLocation : location}>{getPageRoutes(this.isModal)}</Switch>
                <Switch>{this.isModal ? getModalRoutes() : null}</Switch>
            </React.Fragment>
        );
    }

    private get lastLocation() {
        return this.props.location.state.lastLocation;
    }

    /**
     * Whether or not the we are navigated inside of a router.
     */
    private get isModal(): boolean {
        const { location } = this.props;
        return !!(location && location.state && location.state.modal);
    }
}
