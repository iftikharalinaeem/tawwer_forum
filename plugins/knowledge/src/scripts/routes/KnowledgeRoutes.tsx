/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { Switch, RouteComponentProps } from "react-router-dom";
import { getPageRoutes } from "@knowledge/routes/pageRoutes";
import { getModalRoutes } from "@knowledge/routes/modalRoutes";
import { Location } from "history";

type IProps = RouteComponentProps<{}>;

interface IState {
    appLocation: Location;
}

/**
 * Routing component for pages in modals in the /kb directory.
 */
export default class KnowledgeRoutes extends React.Component<IProps, IState> {
    public constructor(props: IProps) {
        super(props);
        this.state = {
            appLocation: props.location,
        };
    }

    /**
     * We can pass a location to <Switch/>
     * that will tell it to ignore the router's current location
     * and use the location prop instead.
     *
     * Using this we can keep the last non-modal state,
     * so we can match both a modal route (what shows in the url bar)
     * while still having the previous context match behind it.
     *
     * @param prevProps
     */
    public componentDidUpdate(prevProps: IProps) {
        const { location, history } = this.props;
        const prevLocation = prevProps.location;
        // set previousLocation if props.location is not modal
        if (
            location !== prevLocation &&
            history.action !== "POP" &&
            prevLocation &&
            (!prevLocation.state || !prevLocation.state.modal)
        ) {
            this.setState({
                appLocation: prevLocation,
            });
        }
    }

    public render() {
        const { location } = this.props;

        return (
            <React.Fragment>
                <Switch location={this.isModal ? this.state.appLocation : location}>
                    {getPageRoutes(this.isModal)}
                </Switch>
                <Switch>{this.isModal ? getModalRoutes() : null}</Switch>
            </React.Fragment>
        );
    }

    private get isModal(): boolean {
        const { location } = this.props;
        return !!(location && location.state && location.state.modal && this.state.appLocation !== location);
    }
}
