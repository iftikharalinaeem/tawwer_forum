/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { Provider } from "react-redux";
import getStore, { getHistory } from "@library/state/getStore";
import KnowledgeRoutes from "@knowledge/routes/KnowledgeRoutes";
import DeviceContext from "@knowledge/contexts/DeviceContext";
import DeviceChecker, { Devices } from "@knowledge/components/DeviceChecker";
import { Route } from "react-router-dom";
import { ConnectedRouter } from "connected-react-router";

/**
 * Top level application component for knowledge.
 * This is made to mounted with ReactDOM.
 */
export default class KnowledgeApp extends React.Component {
    public deviceChecker: React.RefObject<DeviceChecker> = React.createRef();

    /**
     * Device checker detects device and calls a force update if needed to update the current device.
     */
    public render() {
        const store = getStore();
        const history = getHistory();
        return (
            <Provider store={store}>
                <React.Fragment>
                    <DeviceChecker ref={this.deviceChecker} doUpdate={this.doUpdate} />
                    <DeviceContext.Provider
                        value={this.deviceChecker.current ? this.deviceChecker.current.device : Devices.DESKTOP}
                    >
                        <ConnectedRouter history={history}>
                            <Route component={KnowledgeRoutes} />
                        </ConnectedRouter>
                    </DeviceContext.Provider>
                </React.Fragment>
            </Provider>
        );
    }

    /**
     * After the component mounts we need to update it so the results from the deviceChecker ref get passed through.
     */
    public componentDidMount() {
        this.doUpdate();
    }

    /**
     * Function to force rerendering
     */
    private doUpdate = () => {
        this.forceUpdate();
    };
}
